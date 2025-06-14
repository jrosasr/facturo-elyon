<?php

namespace App\Observers;

use App\Models\Invoice;
use Filament\Notifications\Notification;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $receiver = auth()->user();

        Notification::make()
            ->title('Resgistro creado')
            ->body('La factura ha sido creada exitosamente.')
            ->danger()
            ->sendToDatabase($receiver);
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $receiver = auth()->user();

        Notification::make()
            ->title('Resgistro actualizado')
            ->body('La factura ha sido actualizada exitosamente.')
            ->danger()
            ->sendToDatabase($receiver);
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        //
    }
}
