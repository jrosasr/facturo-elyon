<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Colors\Colors;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Alignment;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action; // Asegúrate de que esta importación está aquí
use Filament\Tables\Actions\Action as TableAction; // Importa TableAction
use Illuminate\Support\Facades\View; // Importa la clase View

use Illuminate\Support\Facades\Log;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Factura';
    protected static ?string $pluralModelLabel = 'Facturas';

    // protected static ?string $navigationGroup = 'Configuración';
    // protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Cliente')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->relationship('client', 'name')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('phone'),
                        Forms\Components\Textarea::make('address'),
                    ]),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'unpaid' => 'Pendiente',
                        'paid' => 'Pagada',
                        'canceled' => 'Cancelada',
                    ])
                    ->default('unpaid')
                    ->required()
                    ->label('Estado')
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
                
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                
                Forms\Components\Repeater::make('invoice_products')
                    ->label('Productos')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Producto')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->options(Product::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $product = Product::find($state);
                                if ($product) {
                                    $set('price', $product->price);
                                    $set('available_stock', $product->stock);
                                }
                            }),
                        
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $price = $get('price') ?? 0;
                                $set('subtotal', $state * $price);
                            }),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('Precio Unitario')
                            ->numeric()
                            ->required()
                            ->reactive() // Agregar esto
                            ->prefix('$')
                            ->default(0)
                            ->afterStateHydrated(function ($state, Forms\Set $set) {
                                // Convertir de centavos a dólares al cargar
                                $set('price', $state);
                            })
                            ->dehydrateStateUsing(fn ($state) => $state * 100), // Convertir a centavos al guardar


                        
                        Forms\Components\Placeholder::make('subtotal')
                            ->label('Subtotal')
                            ->content(function (Forms\Get $get) {
                                return '$' . number_format($get('quantity') * $get('price'), 2);
                            }),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->live(),
                
                Forms\Components\Placeholder::make('total')
                    ->label('Total Factura')
                    ->content(function (Forms\Get $get) {
                        $total = collect($get('invoice_products'))->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                        return '$' . number_format($total, 2);
                    })
                    ->extraAttributes(['class' => 'text-xl font-bold'])
                    ->columnSpanFull(),
                
                Forms\Components\Textarea::make('details')
                    ->label('Notas')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                TableAction::make('generate_pdf')  // Usa TableAction aquí
                ->label('Generar PDF')
                ->action(function (Invoice $record) {
                    // Obtener los datos necesarios para la factura
                    $invoiceData = $record->toArray();
                    $productsData = $record->products->toArray();
                    $clientData = $record->client->toArray();
                    $userData = $record->user->toArray();

                    // Pasar los datos a la vista (asegúrate de crear esta vista)
                    $pdf = Pdf::loadView('pdf.invoice', [
                        'invoice' => $invoiceData,
                        'products' => $productsData,
                        'client' => $clientData,
                        'user' => $userData,
                    ]);

                    // Descargar el PDF
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, "factura-{$record->id}.pdf");
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    if ($record->status !== 'canceled') {
                                        $record->restoreStock();
                                    }
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

}