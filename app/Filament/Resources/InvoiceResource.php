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

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Colors\Colors;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Alignment;

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
                Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('client_id')
                                ->label('Cliente')
                                ->required()
                                ->placeholder('Selecciona un cliente')
                                ->preload()
                                ->searchable()
                                ->relationship('client', 'name')
                                ->options(Client::all()->pluck('name', 'id'))
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->label('Nombre'),
                                    Forms\Components\TextInput::make('phone')
                                        ->tel()
                                        ->label('Teléfono'),
                                    Forms\Components\TextInput::make('address')
                                        ->label('Dirección'),
                                    Forms\Components\TextInput::make('notes')
                                        ->label('Notas'),
                                    Forms\Components\Hidden::make('user_id')
                                        ->default(auth()->id()),
                                ]),
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->maxDate(now())
                                    ->default(now())
                                    ->placeholder('Selecciona una fecha')
                                    ->reactive()
                                    ->label('Fecha'),
                                Forms\Components\Select::make('status')
                                    ->label('Estatus')
                                    ->placeholder('Selecciona un estatus')
                                    ->options([
                                        'paid' => 'Pagado',
                                        'unpaid' => 'Pendiente',
                                        'canceled' => 'Cancelado',
                                    ])
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('details')
                            ->columnSpanFull()
                            ->label('Detalles'),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                Repeater::make('invoice_products')
                    ->label('Lista de productos')
                    ->schema([
                        Forms\Components\Grid::make(4) // 4 columnas
                            ->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Producto')
                                    ->relationship('products', 'name')
                                    ->options(function (Forms\Get $get, Forms\Set $set) {
                                        $selectedProducts = collect($get('invoice_products'))
                                            ->pluck('product_id')
                                            ->filter()
                                            ->toArray();
                                        Log::info($selectedProducts);

                                        return \App\Models\Product::whereNotIn('id', $selectedProducts)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $product = \App\Models\Product::find($state);
                                        if ($product) {
                                            $set('price', $product->price);
                                            $set('quantity', 1);
                                        } else {
                                            $set('price', 0);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->reactive()
                                    ->name('quantity')
                                    ->afterStateUpdated(function ($get, Forms\Set $set) {
                                        $set('total_price', $get('quantity') * $get('price'));
                                    }),
                                TextInput::make('price')
                                    ->label('Precio')
                                    ->numeric()
                                    ->reactive()
                                    ->name('price')
                                    ->required()
                                    ->afterStateUpdated(function ($get, Forms\Set $set) {
                                        $set('total_price', $get('quantity') * $get('price'));
                                    }),
                                TextInput::make('total_price')
                                    ->label('Cantidad * Precio')
                                    ->numeric()
                                    ->disabled(),
                            ]),
                    ])
                    ->addActionLabel('Agregar')
                    ->columnSpanFull(),

                Placeholder::make('total_amount')
                    ->label('Total')
                    ->content(function ($get) {
                        $total = collect($get('invoice_products'))
                            ->sum(function ($item) {
                                return $item['quantity'] * $item['price'];
                            });
                        return "Total: " . number_format($total, 2); // Formatea el total
                    })
                    ->extraAttributes(['class' => 'text-right text-xl font-bold'])
                    ->columnSpanFull(),
            ]);
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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