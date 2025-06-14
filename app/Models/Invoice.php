<?php

namespace App\Models;

use App\Casts\Money;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Invoice extends Model
{
    protected $fillable = [
        'date',
        'status',
        'details',
        'client_id',
        'total',
        'team_id',
    ];

    protected $casts = [
        'total' => Money::class,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'invoice_product', 'invoice_id', 'product_id')
            ->withPivot('quantity', 'price');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function restoreStock(): void
    {
        foreach ($this->products as $product) {
            $product->stock += $product->pivot->quantity;
            $product->save();
        }
    }

    protected static function booted(): void
    {
        static::deleted(function (Invoice $invoice) {
            if ($invoice->status !== 'canceled') {
                $invoice->restoreStock();
            }
        });
    }
}
