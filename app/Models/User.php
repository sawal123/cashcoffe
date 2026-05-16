<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,  HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'branch_id',
        'jabatan_id',
        'gaji_pokok',
        'tunjangan_harian',
        'hak_cuti',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function member()
    {
        return $this->hasOne(Member::class);
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'user_id');
    }
    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }
    
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }
}
