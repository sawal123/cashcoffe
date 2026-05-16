<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'logo',
        'icon',
        'default_potongan_terlambat',
        'default_potongan_alpha',
        'office_name',
        'office_latitude',
        'office_longitude',
        'office_radius',
    ];
}
