<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Importa Notification
use Illuminate\Database\Eloquent\Model; // Import Model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        DB::beginTransaction(); // Iniciar transacción para asegurar la integridad de los datos
        try {
            $invoice = static::getModel()::create([
                'date' => $data['date'],
                'status' => $data['status'],
                'details' => $data['details'],
                'client_id' => $data['client_id'],
                'user_id' => auth()->id(),
            ]);

            // Formatear los datos para sync()
            $productsData = [];
            foreach ($data['invoice_products'] as $product) {
                $productsData[$product['product_id']] = [
                    'quantity' => $product['quantity'],
                    'price' => $product['price'] * 100,
                ];
            }

            Log::info('Invoice ID: ' . $invoice->id);
            Log::info('Products Data: ' . json_encode($productsData));

            $invoice->products()->sync($productsData);

            DB::commit(); // Confirmar la transacción

            return $invoice;
        } catch (\Exception $e) {
            DB::rollback(); // Revertir la transacción en caso de error
            Log::error('Error creating invoice: ' . $e->getMessage());
            throw $e; // Re-throw the exception to see the error
        }
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     $data['user_id'] = auth()->id();
    //     return $data;
    // }

    // protected function handleRecordUpdate(Model $record, array $data): Model
    // {
    //     $record->update([
    //         'date' => $data['date'],
    //         'status' => $data['status'],
    //         'details' => $data['details'],
    //     ]);

    //     $productsData = [];
    //     foreach ($data['invoice_products'] as $product) {
    //         $productsData[$product['product_id']] = ['quantity' => $product['quantity'], 'price' => $product['price']];
    //     }

    //     $record->products()->sync($productsData);

    //     return $record;
    // }

    // protected function getCreatedNotification(): ?Notification
    // {
    //     return Notification::make()
    //         ->success()
    //         ->title('Factura Creada')
    //         ->body('La factura ha sido creada exitosamente.');
    // }

    // protected function getUpdatedNotification(): ?Notification
    // {
    //     return Notification::make()
    //         ->success()
    //         ->title('Factura Actualizada')
    //         ->body('La factura ha sido actualizada exitosamente.');
    // }
}
