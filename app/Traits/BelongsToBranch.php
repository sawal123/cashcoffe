<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToBranch
{
    /**
     * Boot the trait and apply the global scope.
     * Also automatically assign branch_id on creation.
     */
    protected static function bootBelongsToBranch()
    {
        // Event listener to auto-assign branch_id when creating new records
        static::creating(function (Model $model) {
            if (auth()->check() && auth()->user()->branch_id != null) {
                // Jika data yang dibuat belum punya branch_id, isi otomatis
                if (empty($model->branch_id)) {
                    $model->branch_id = auth()->user()->branch_id;
                }
            }
        });

        // Global Scope to filter by branch
        static::addGlobalScope('branch_filter', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // JIKA BUKAN SUPERADMIN, filter berdasarkan branch_id
                // Menggunakan method hasRole dari Spatie
                if ($user->branch_id != null && !$user->hasRole('superadmin')) {
                    $builder->where($builder->getQuery()->from . '.branch_id', $user->branch_id);
                }
            }
        });
    }

    /**
     * Define the relationship to the Branch model.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
