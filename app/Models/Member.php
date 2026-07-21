<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = ['user_id', 'phone', 'address', 'points', 'total_pengeluaran'];

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = PhoneNumber::member($value);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
