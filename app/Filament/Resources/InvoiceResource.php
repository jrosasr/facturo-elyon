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
use Illuminate\Support\Facades\Storage;

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
use Illuminate\Support\Collection; // Importa Collection correctamente

use Illuminate\Support\Facades\Log;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Factura';
    protected static ?string $pluralModelLabel = 'Facturas';

    protected static ?string $navigationGroup = 'Facturación';
    protected static ?int $navigationSort = 1;

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
                    ->disabled(fn (Forms\Get $get): bool => $get('status') === 'canceled' || $get('status') === 'paid')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('phone'),
                        Forms\Components\Textarea::make('address'),
                        Forms\Components\Hidden::make('team_id')
                            ->default(optional(optional(auth()->user())->currentTeam())->id),
                    ]),

                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    // deshabilitar cuando el estatus es canceled
                    ->disabled(fn (Forms\Get $get): bool => $get('status') === 'canceled' || $get('status') === 'paid')
                    ->default(now()),

                Forms\Components\Repeater::make('invoice_products')
                    ->label('Productos')
                    ->disabled(fn (Forms\Get $get): bool => $get('status') === 'canceled' || $get('status') === 'paid')
                    ->schema([
                        Forms\Components\Hidden::make('team_id')
                            ->default(optional(optional(auth()->user())->currentTeam())->id),
                        Forms\Components\Select::make('product_id')
                            ->label('Producto')
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->options(function () {
                                $teamId = optional(optional(auth()->user())->currentTeam())->id;
                                return $teamId ? Product::where('team_id', $teamId)->pluck('name', 'id') : [];
                            })
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
                    ->disabled(fn (Forms\Get $get): bool => $get('status') === 'canceled' || $get('status') === 'paid')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'gray',
                        'canceled' => 'danger',
                        'paid' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unpaid' => 'En Proceso',
                        'canceled' => 'Cancelada',
                        'paid' => 'Completada',
                        default => $state,
                    })
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
                TableAction::make('generate_pdf')  // Usa TableAction aquí
                    ->visible(fn (Invoice $record) => isset($record->status) && $record->status === 'paid')
                    ->label('Generar Factura')
                    ->color('info')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Invoice $record) {
                        // Obtener los datos necesarios para la factura
                        $invoiceData = $record->toArray();
                        $productsData = $record->products->toArray();
                        $clientData = $record->client->toArray();
                        $team = optional(optional(auth()->user())->currentTeam());
                        $teamLogoPath = $team->logo ?? null;
                        $logoExists = $teamLogoPath ? Storage::disk('public')->exists($teamLogoPath) : false;

                        $base64Logo = '';
                        if ($logoExists) {
                            $logoContents = Storage::disk('public')->get($teamLogoPath);
                            $base64Logo = 'data:image/' . pathinfo($teamLogoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoContents);
                        }

                        // Get the team details
                        $teamName = $team->name ?? '';
                        $teamRif = $team->rif ?? '';
                        $teamAddress = $team->address ?? '';

                        // Pasar los datos a la vista (asegúrate de crear esta vista)
                        $pdf = Pdf::loadView('pdf.invoice', [
                            'invoice' => $invoiceData,
                            'products' => $productsData,
                            'client' => $clientData,
                            'base64Logo' => $base64Logo,
                            'teamName' => $teamName,
                            'teamRif' => $teamRif,
                            'teamAddress' => $teamAddress,
                        ]);

                        // Descargar el PDF
                        return response()->streamDownload(function () use ($pdf, $record) {
                            echo $pdf->stream();
                        }, "factura-" . ($record->id ?? 'sin_id') . ".pdf");
                    }),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (Invoice $record) => isset($record->status) && $record->status === 'unpaid'),
                    // Tables\Actions\DeleteAction::make()
                    //     ->visible(fn (Invoice $record) => isset($record->status) && $record->status === 'canceled')
                    //     ->before(function (TableAction $action, Invoice $record) {
                    //         if (!isset($record->status) || $record->status !== 'canceled') {
                    //             throw new \Exception('Solo se pueden borrar facturas canceladas.');
                    //         }
                    //     }),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make()
                //         ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                //             DB::transaction(function () use ($records) {
                //                 foreach ($records as $record) {
                //                     if (!isset($record->status) || $record->status !== 'canceled') {
                //                         throw new \Exception('Solo se pueden borrar facturas canceladas.');
                //                     }
                //                     $record->restoreStock();
                //                 }
                //             });
                //         }),
                // ]),
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