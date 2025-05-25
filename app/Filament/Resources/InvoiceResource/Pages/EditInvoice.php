<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model; // Import Model

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
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
        // Ensure 'invoice_products' key exists and is an array
        $data['invoice_products'] = [];

        // Load the invoice with its products and pivot data
        // Use $this->getRecord() to get the current Invoice model instance
        $invoice = $this->getRecord()->load('products'); // Eager load the 'products' relation

        // Check if the invoice and its products relation exist
        if ($invoice && $invoice->products) {
            // Iterate over each product in the relation to format it for the Repeater
            foreach ($invoice->products as $product) {
                $data['invoice_products'][] = [
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price / 100, // Divide by 100 for display in form
                    'total_price' => ($product->pivot->quantity * $product->pivot->price) / 100, // Calculate total for display
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
        // Update the main invoice record fields
        $record->update([
            'client_id' => $data['client_id'],
            'date' => $data['date'],
            'status' => $data['status'],
            'details' => $data['details'],
        ]);
        
        // Prepare data for syncing products with pivot attributes
        $productsData = [];
        // Check if 'invoice_products' exists and is an array to prevent errors
        if (isset($data['invoice_products']) && is_array($data['invoice_products'])) {
            foreach ($data['invoice_products'] as $product) {
                // Ensure product_id is set before using it as an array key
                if (isset($product['product_id'])) {
                    $productsData[$product['product_id']] =
                    [
                        'quantity' => $product['quantity'],
                        'price' => $product['price'] * 100, // Multiply by 100 for storage in cents
                    ];
                }
            }
        }

        // Sync the products relation with the new/updated pivot data
        $record->products()->sync($productsData);

        return $record;
    }

}
