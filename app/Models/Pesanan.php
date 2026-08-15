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
        if ($this->discount_id && $this->discount) {
            if ($this->discount->scope === 'global' && $this->discount_value > 0 && $this->discount->digunakan > 0) {
                $this->discount->decrement('digunakan');
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

        // 2. Lock Row sesuai ID berurutan (cegah deadlock)
        $ingredientIds = array_keys($stockChanges);

        $lockedIngredients = Ingredients::whereIn('id', $ingredientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // 3. Validasi Stok (Cegah stok negatif)
        foreach ($stockChanges as $ingredientId => $neededQty) {
            $ingredient = $lockedIngredients->get($ingredientId);
            if (!$ingredient) {
                // Fail-safe: throw exception untuk trigger rollback
                throw new \Exception("Bahan baku dengan ID {$ingredientId} tidak ditemukan.");
            }

            if ($ingredient->stok < $neededQty) {
                throw new \Exception("Stok tidak mencukupi untuk bahan: {$ingredient->nama_bahan}");
            }
        }

        // 4. Eksekusi Pemotongan Stok dan Pembuatan Riwayat
        foreach ($stockChanges as $ingredientId => $neededQty) {
            $ingredient = $lockedIngredients->get($ingredientId);
            if (!$ingredient) continue;

            $before = $ingredient->stok;
            $after = $before - $neededQty;

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
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
