<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'symbol',
        'code',
        'name',
        'available',
        'decimal_places',
        'decimal_separator',
        'thousands_separator',
    ];

    protected $casts = [
        'available' => 'boolean',
        'decimal_places' => 'integer',
    ];

    public function getFormattedAmountAttribute($amount)
    {
        return number_format($amount, $this->decimal_places, $this->decimal_separator, $this->thousands_separator);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
