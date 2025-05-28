<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model; // Import Model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use App\Models\Product;

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
            Actions\Action::make('cancel')
                ->label('Cancelar Factura')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->action(function () {
                    DB::transaction(function () {
                        $this->getRecord()->update(['status' => 'canceled']);
                        $this->getRecord()->restoreStock();
                        Notification::make()
                            ->success()
                            ->title('Factura cancelada')
                            ->body('El stock de productos ha sido restaurado.')
                            ->send();
                    });
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->getRecord()->status !== 'canceled'),
            
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    DB::transaction(function () {
                        if ($this->getRecord()->status !== 'canceled') {
                            $this->getRecord()->restoreStock();
                        }
                    });
                }),
        ];
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
                    'price' => $product->pivot->price / 100,
                    'total_price' => ($product->pivot->quantity * $product->pivot->price) / 100,
                ];
            }
        }

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
            // Validación de stock
            $insufficientStockProducts = [];
            foreach ($data['invoice_products'] as $product) {
                $productModel = Product::find($product['product_id']);
                $originalQuantity = $record->products->find($product['product_id'])?->pivot->quantity ?? 0;
                $quantityDifference = $product['quantity'] - $originalQuantity;

                if ($productModel->stock - $quantityDifference < 0) {
                    $insufficientStockProducts[] = $productModel->name;
                }
            }

            if (!empty($insufficientStockProducts)) {
                // ... (mantener tu lógica de error)
            }

            $record->update([
                'client_id' => $data['client_id'],
                'date' => $data['date'],
                'status' => $data['status'],
                'details' => $data['details'],
            ]);

            $productsData = [];
            foreach ($data['invoice_products'] as $product) {
                $productsData[$product['product_id']] = [
                    'quantity' => $product['quantity'],
                    'price' => $product['price'], // Ya viene en centavos
                ];

                $productModel = Product::find($product['product_id']);
                $originalQuantity = $record->products->find($product['product_id'])?->pivot->quantity ?? 0;
                $quantityDifference = $product['quantity'] - $originalQuantity;
                $productModel->stock -= $quantityDifference;
                $productModel->save();
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
