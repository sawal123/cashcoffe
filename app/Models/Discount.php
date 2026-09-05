<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'member_only' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_akhir' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Branch Accessibility (multi-tenant)
    |--------------------------------------------------------------------------
    | Semantics:
    | - branch_id = NULL  => shared discount, dapat DIGUNAKAN semua branch
    | - branch_id = X     => discount hanya milik branch X
    | Field `scope` (global/category/item) HANYA menentukan jenis penerapan
    | discount dan TIDAK menentukan branch access.
    */

    /**
     * Discount yang boleh DIGUNAKAN oleh user.
     * - superadmin: semua
     * - non-superadmin dengan branch_id: branch NULL ATAU branch sendiri
     * - non-superadmin tanpa branch_id: hanya branch NULL
     */
    public function scopeAccessibleTo(Builder $query, ?User $user): Builder
    {
        if ($user && $user->hasRole('superadmin')) {
            return $query;
        }

        if ($user && $user->branch_id) {
            return $query->where(function (Builder $q) use ($user) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        return $query->whereNull('branch_id');
    }

    /**
     * Discount yang boleh DIKELOLA (lihat/edit/delete) oleh user.
     * - superadmin: semua (termasuk shared)
     * - non-superadmin dengan branch_id: hanya branch sendiri
     * - non-superadmin tanpa branch_id: tidak ada
     * Shared discount (branch_id = NULL) hanya dikelola superadmin.
     */
    public function scopeManageableBy(Builder $query, ?User $user): Builder
    {
        if ($user && $user->hasRole('superadmin')) {
            return $query;
        }

        if ($user && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function isAccessibleTo(?User $user): bool
    {
        if ($user && $user->hasRole('superadmin')) {
            return true;
        }

        if ($user && $user->branch_id) {
            return $this->branch_id === null
                || (int) $this->branch_id === (int) $user->branch_id;
        }

        return $this->branch_id === null;
    }

    public function isManageableBy(?User $user): bool
    {
        if ($user && $user->hasRole('superadmin')) {
            return true;
        }

        return (bool) $user
            && $user->branch_id !== null
            && (int) $this->branch_id === (int) $user->branch_id;
    }

    public function isMemberOnly(): bool
    {
        return (bool) $this->member_only;
    }

    public function canBeUsedBy(?Member $member): bool
    {
        return ! $this->isMemberOnly() || $member !== null;
    }

    public function canBeUsedByMemberId(?int $memberId): bool
    {
        return ! $this->isMemberOnly() || $memberId !== null;
    }

    /**
     * Single source of truth untuk actual usage reconciliation.
     * Jika `digunakan` NULL (legacy), hitung active orders TANPA branch_filter
     * (internal accounting lintas branch), lalu terapkan ownership discount:
     * - branch_id NULL  => count pesanan dari SEMUA branch
     * - branch_id X     => count hanya pesanan branch_id = X
     */
    public function reconciledUsage(): int
    {
        $current = $this->digunakan;

        if (! is_null($current)) {
            return (int) $current;
        }

        return (int) Pesanan::query()
            ->withoutGlobalScope('branch_filter')
            ->where('discount_id', $this->id)
            ->where('discount_value', '>', 0)
            ->whereNotIn('status', [Pesanan::STATUS_DIBATALKAN])
            ->whereNull('deleted_at')
            ->when($this->branch_id !== null, function (Builder $query) {
                $query->where('branch_id', $this->branch_id);
            })
            ->count();
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'discount_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function priceTier()
    {
        return $this->belongsTo(PriceTier::class);
    }

    public function discountItems()
    {
        return $this->hasMany(DiscountItem::class);
    }
}
