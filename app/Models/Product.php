<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Casts\Money;
use App\Models\Team;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cost',
        'price',
        'stock',
        'stock_min',
        'status',
        'image',
        'category_id',
        'team_id',
    ];

    protected $casts = [
        'cost' => Money::class,
        'price' => Money::class,
        'stock' => 'integer',
        'stock_min' => 'integer',
    ];

    public function categories(): BelongsToMany // Cambia el nombre y el tipo de relación
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'invoice_product', 'product_id', 'invoice_id')
            ->withPivot('quantity', 'price');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
