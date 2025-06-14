<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'unpaid';

        // Calculate total from invoice_products before creation
        $calculatedTotal = 0;
        foreach ($data['invoice_products'] as $product) {
            // Price comes in cents, no division by 100 needed here if it's already in cents.
            // If the price in the form is in dollars/VEF and then converted to cents,
            // ensure 'price' is already in cents when it reaches this point.
            $calculatedTotal += ($product['quantity'] * $product['price']);
        }
        $data['total'] = $calculatedTotal / 100; // Assign the calculated total to the data array

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

                // Throw a ValidationException to display an error on the form
                throw ValidationException::withMessages([
                    'invoice_products' => 'No hay suficiente stock para los siguientes productos: ' . implode(', ', $insufficientStockProducts),
                ]);
            }

            $invoice = static::getModel()::create([
                'date' => $data['date'],
                'status' => $data['status'],
                'details' => $data['details'],
                'client_id' => $data['client_id'],
                'total' => $data['total'],
                'team_id' => auth()->user()->currentTeam()->id
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

                $receiver = auth()->user();

                if ($productModel->stock <= $productModel->stock_min) {
                    Notification::make()
                        ->title('Alerta de Stock Bajo')
                        ->body('Solo quedan ' . $productModel->stock . ' unidades de ' . $productModel->name)
                        ->danger()
                        ->sendToDatabase($receiver);
                }
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
