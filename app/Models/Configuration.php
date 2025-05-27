<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo',
        'rif',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
