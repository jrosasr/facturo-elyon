<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Product;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\InvoiceResource;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model; // Import Model

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('paid')
                ->label('Completar Factura')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->before(function () {
                    DB::transaction(function () {
                        $this->getRecord()->update(['status' => 'paid']);
                        Notification::make()
                            ->success()
                            ->title('Factura completada')
                            ->body('La factura ha sido marcada como pagada.')
                            ->send();
                    });
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->getRecord()->status === 'unpaid'),
            Actions\Action::make('canceled')
                ->label('Cancelar Factura')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->before(function () {
                    DB::transaction(function () {
                        $this->getRecord()->update(['status' => 'canceled']);
                        $this->getRecord()->restoreStock();
                        Notification::make()
                            ->success()
                            ->title('Cancelar factura')
                            ->body('El stock de productos ha sido restaurado.')
                            ->send();
                    });
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->getRecord()->status === 'unpaid'),
            Actions\Action::make('unpaid')
                ->label('Restaurar Factura')
                ->color('gray')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->before(function () {
                    DB::transaction(function () {
                        $this->getRecord()->update(['status' => 'unpaid']);
                        Notification::make()
                            ->success()
                            ->title('Factura restaurada')
                            ->body('La factura ha sido restaurada y puesta de nuevo en estado pendiente.')
                            ->send();
                    });
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->getRecord()->status === 'canceled'),
        ];
    }

    protected function getFormActions(): array
    {
        $options = [
            ...parent::getFormActions()
        ];

        if ($this->getRecord()->status === 'canceled' || $this->getRecord()->status === 'paid') {
            $options = [];
        }
        return $options;
    }

    /**
     * Mutate the form data before it is filled into the form.
     * This is crucial for populating the Repeater with BelongsToMany pivot data.
     *
     * @param array $data The original data array from the record.
     * @return array The modified data array for the form.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['invoice_products'] = [];
        $invoice = $this->getRecord()->load('products');

        if ($invoice && $invoice->products) {
            foreach ($invoice->products as $product) {
                $data['invoice_products'][] = [
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price, // Keep price in cents for calculations
                    'total_price' => ($product->pivot->quantity * $product->pivot->price), // This is the total for the line item in cents
                ];
            }
        }
        // The total for the entire invoice will be fetched directly from the 'total' column
        // No explicit 'total' entry needed in $data for filling if it's not a form field directly.
        // If you have a Filament field for 'total' on the form, it will be automatically populated.

        return $data;
    }


    /**
     * Handle the record update process.
     * This method is responsible for updating the main invoice record and syncing its products.
     *
     * @param Model $record The Eloquent model instance being updated.
     * @param array $data The validated form data.
     * @return Model The updated Eloquent model instance.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::beginTransaction();
        try {
            // Validation for stock before any changes
            $insufficientStockProducts = [];
            foreach ($data['invoice_products'] as $product) {
                $productModel = Product::find($product['product_id']);
                $originalQuantity = $productModel->stock + $record->products->find($product['product_id'])?->pivot->quantity ?? 0;
                $quantityDifference = $product['quantity'] - $originalQuantity;

                if ($originalQuantity - $product['quantity'] < 0) {
                    $insufficientStockProducts[] = $productModel->name;
                }
            }

            if (!empty($insufficientStockProducts)) {
                DB::rollback();
                Notification::make()
                    ->title('Error al actualizar la factura')
                    ->body('No hay suficiente stock para los siguientes productos: ' . implode(', ', $insufficientStockProducts))
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'invoice_products' => 'No hay suficiente stock para los siguientes productos: ' . implode(', ', $insufficientStockProducts),
                ]);
            }

            // Calculate the new total for the invoice
            $newCalculatedTotal = 0;
            foreach ($data['invoice_products'] as $product) {
                $newCalculatedTotal += ($product['quantity'] * $product['price']);
            }

            $record->update([
                'client_id' => $data['client_id'],
                'date' => $data['date'],
                'status' => $record->status, // Status is handled by header actions
                'details' => $data['details'],
                'total' => $newCalculatedTotal / 100, // Convert total back to dollars
            ]);

            $productsData = [];
            foreach ($data['invoice_products'] as $product) {
                $productsData[$product['product_id']] = [
                    'quantity' => $product['quantity'],
                    'price' => $product['price'], // Price already in cents
                ];

                $productModel = Product::find($product['product_id']);
                $originalQuantity = $record->products->find($product['product_id'])?->pivot->quantity ?? 0;
                $quantityDifference = $product['quantity'] - $originalQuantity;
                $productModel->stock -= $quantityDifference; // Adjust stock based on difference
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

            $record->products()->sync($productsData);
            DB::commit();
            return $record;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}