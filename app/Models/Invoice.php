<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute; // Import Attribute class


class Invoice extends Model
{
    protected $fillable = [
        'date',
        'status',
        'details',
        'client_id',
        'user_id',
    ];

    protected $appends = [
        'total',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->products->sum(fn ($product) => $product->pivot->quantity * $product->pivot->price),
        );
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
