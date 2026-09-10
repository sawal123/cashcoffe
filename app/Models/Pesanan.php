<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToBranch;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\MenuIngredients;
use App\Models\VariantOption;
use Illuminate\Support\Str;

class Pesanan extends Model
{
    use SoftDeletes, BelongsToBranch;

    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'branch_id',
        'kode',
        'nama',
        'user_id',
        'member_id',
        'mejas_id',
        'discount_id',
        'status',
        'metode_pembayaran',
        'payment_method_id',
        'sales_channel_id',
        'total',
        'total_profit',
        'discount_value',
        'catatan',
        'uang_tunai',
        'kembalian'
    ];

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === self::STATUS_DIPROSES) {
            return in_array($targetStatus, [self::STATUS_SELESAI, self::STATUS_DIBATALKAN], true);
        }

        return false;
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DIPROSES;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_SELESAI, self::STATUS_DIBATALKAN], true);
    }

    public function decrementDiscountUsageOnCancellation(): void
    {
        if (! $this->discount_id || $this->discount_value <= 0) {
            return;
        }

        $discount = \App\Models\Discount::where('id', $this->discount_id)
            ->lockForUpdate()
            ->first();

        if (! $discount || $discount->scope !== 'global') {
            return;
        }

        // Legacy/malformed foreign-branch reference:
        // pembatalan order tetap berjalan, tetapi counter discount
        // cabang lain TIDAK boleh disentuh.
        $ownedByOrderBranch = $discount->branch_id === null
            || ($this->branch_id !== null
                && (int) $discount->branch_id === (int) $this->branch_id);

        if (! $ownedByOrderBranch) {
            return;
        }

        // NULL-safe reconciliation lintas branch (shared discount = global).
        $currentUsage = $discount->reconciledUsage();

        // Always persist numeric value (never leave NULL, never go negative)
        $discount->update(['digunakan' => max(0, $currentUsage - 1)]);
    }

    public function applyMemberLoyaltyOnCompletion(): void
    {
        if ($this->member_id) {
            $member = \App\Models\Member::where('id', $this->member_id)
                ->lockForUpdate()
                ->first();

            if ($member) {
                $totalCents = (int) round(((float) $this->total) * 100);
                $discCents  = (int) round(((float) $this->discount_value) * 100);
                $finalCents = max(0, $totalCents - $discCents);

                $earnedPoints = intdiv($finalCents, 1000000);

                $currentPoints = (int) ($member->points ?? 0);
                $currentCents  = (int) round(((float) ($member->total_pengeluaran ?? 0)) * 100);

                $newTotalCents = $currentCents + $finalCents;
                $newTotalFormatted = number_format($newTotalCents / 100, 2, '.', '');

                $member->update([
                    'points'            => $currentPoints + $earnedPoints,
                    'total_pengeluaran' => $newTotalFormatted,
                ]);
            }
        }
    }

    public function processInventoryDeduction(): void
    {
        $stockChanges = [];

        // 1. Agregasi Kebutuhan Bahan
        foreach ($this->items as $item) {
            // Resep dasar
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (!isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = 0;
                }
                $stockChanges[$k->ingredient_id] += ($k->qty * $item->qty);
            }

            // Resep varian
            $selectedVariantIds = $item->variants()->pluck('variant_options.id')->toArray();
            if (!empty($selectedVariantIds)) {
                $variantPivot = \Illuminate\Support\Facades\DB::table('variant_option_ingredients')
                    ->whereIn('variant_option_id', $selectedVariantIds)
                    ->get();

                foreach ($variantPivot as $pivot) {
                    if (!isset($stockChanges[$pivot->ingredient_id])) {
                        $stockChanges[$pivot->ingredient_id] = 0;
                    }
                    $stockChanges[$pivot->ingredient_id] += ($pivot->qty * $item->qty);
                }
            }
        }

        if (empty($stockChanges)) {
            return;
        }

        // 2. Resolve order branch sebagai single source of truth
        $orderBranchId = $this->branch_id;
        if (empty($orderBranchId)) {
            throw new \Exception("Pesanan tidak memiliki cabang yang valid untuk pemrosesan inventory.");
        }

        // 3. Lock Row sesuai ID berurutan (cegah deadlock) dengan isolasi order branch
        $ingredientIds = array_keys($stockChanges);

        $lockedIngredients = Ingredients::withoutGlobalScope('branch_filter')
            ->where('branch_id', $orderBranchId)
            ->whereIn('id', $ingredientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // 4. Validasi Keberadaan Bahan Baku
        foreach ($stockChanges as $ingredientId => $neededQty) {
            $ingredient = $lockedIngredients->get($ingredientId);
            if (!$ingredient) {
                // Fail-safe: throw exception untuk trigger rollback
                throw new \Exception("Bahan baku dengan ID {$ingredientId} tidak ditemukan.");
            }
        }

        // 5. Eksekusi Pemotongan Stok dan Pembuatan Riwayat
        foreach ($stockChanges as $ingredientId => $neededQty) {
            $ingredient = $lockedIngredients->get($ingredientId);
            if (!$ingredient) continue;

            $before = $ingredient->stok;
            $after = $before - $neededQty;

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'branch_id'     => $orderBranchId,
                'ingredient_id' => $ingredient->id,
                'kode'          => strtoupper('OUT-' . Str::random(6)),
                'qty'           => $neededQty,
                'qty_before'    => $before,
                'qty_after'     => $after,
                'tipe'          => 'out',
                'keterangan'    => 'Akumulasi resep: pesanan ' . $this->kode,
            ]);
        }
    }

    public function items()
    {
        return $this->hasMany(PesananItem::class, 'pesanans_id');
    }
    public function meja()
    {
        return $this->belongsTo(Meja::class, 'mejas_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function salesChannel()
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
