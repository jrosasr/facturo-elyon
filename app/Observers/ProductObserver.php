<?php

namespace App\Observers;

use App\Models\Product;
use Filament\Notifications\Notification;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $receiver = auth()->user();

        if ($product->stock <= $product->stock_min) {
            Notification::make()
                ->title('Alerta de stock bajo')
                ->body('Solo quedan ' . $product->stock . ' unidades de ' . $product->name)
                ->danger()
                ->sendToDatabase($receiver);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
