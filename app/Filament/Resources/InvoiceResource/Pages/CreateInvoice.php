<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'unpaid'; // Establecer estado pendiente por defecto
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        DB::beginTransaction();
        try {
            // Validación de stock
            $insufficientStockProducts = [];
            foreach ($data['invoice_products'] as $product) {
                $productModel = Product::find($product['product_id']);
                if ($product['quantity'] > $productModel->stock) {
                    $insufficientStockProducts[] = $productModel->name;
                }
            }

            if (!empty($insufficientStockProducts)) {
                DB::rollback();
                Notification::make()
                    ->title('Error al crear la factura')
                    ->body('No hay suficiente stock para los siguientes productos: ' . implode(', ', $insufficientStockProducts))
                    ->danger()
                    ->send();
                return null; // O lanzar una excepción si prefieres
            }

            $invoice = static::getModel()::create([
                'date' => $data['date'],
                'status' => $data['status'],
                'details' => $data['details'],
                'client_id' => $data['client_id'],
                'user_id' => auth()->id(),
            ]);

            $productsData = [];
            foreach ($data['invoice_products'] as $product) {
                $productsData[$product['product_id']] = [
                    'quantity' => $product['quantity'],
                    'price' => $product['price'], // Ya viene en centavos por el dehydrateStateUsing
                ];

                $productModel = Product::find($product['product_id']);
                $productModel->stock -= $product['quantity'];
                $productModel->save();
            }

            $invoice->products()->sync($productsData);
            DB::commit();
            return $invoice;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
