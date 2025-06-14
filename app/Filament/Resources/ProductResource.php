<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action; // Importa la clase Action
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    // protected static ?string $navigationGroup = 'Configuración';
    // protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(250)
                    ->label('Descripción'),
                Forms\Components\TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Costo')
                    ->prefix('$'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Precio')
                    ->prefix('$'),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->minValue(0)
                    ->label('Stock')
                    ->numeric(),
                Forms\Components\TextInput::make('stock_min')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Stock mínimo')
                    ->helperText('El stock mínimo es el nivel de inventario que activa una alerta para reabastecer el producto.')
                    ->default(0),
                Forms\Components\Select::make('category_ids')
                    ->label('Categorías')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('categories', 'name') // Asumiendo que tu relación se llama 'categories' y el nombre es 'name'
                    ->options(Category::all()->pluck('name', 'id')) // Asumiendo que el modelo Category tiene 'name' y 'id'
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la categoría')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción de la categoría'),
                        Forms\Components\Hidden::make('status')
                            ->default('active'),
                        Forms\Components\Hidden::make('team_id')
                            ->default(auth()->user()->currentTeam()->id)
                    ]),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ])->required()
                    ->default('active')
                    ->label('Estatus'),
                Forms\Components\FileUpload::make('image')
                    ->label('Imagen del producto')
                    ->directory('products/images')
                    ->visibility('public')
                    ->image()
                    ->preserveFilenames()
                    ->imagePreviewHeight('150')
                    ->openable()
                    ->downloadable()
                    ->default(function ($record) {
                        return $record?->photo ? [asset('storage/'.$record->photo)] : null;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Costo')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stock_min')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')  // Usamos BadgeColumn
                    ->label('Estado')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TagsColumn::make('categories.name')
                    ->label('Categorías'),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Creado')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->label('Actualizado')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Action::make('generatePdf')
                    ->label('Generar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $productsByCategory = Category::with(['products' => function ($query) {
                            $query->where('team_id', auth()->user()->currentTeam()->id);
                        }])
                            ->whereHas('products', function ($query) {
                                $query->where('team_id', auth()->user()->currentTeam()->id);
                            })
                            ->get();

                        $pdf = Pdf::loadView('pdf.products', ['productsByCategory' => $productsByCategory]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream('products.pdf');
                        }, 'products.pdf');
                    }),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    // public static function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make('Todos'),
    //         'cat_1' => Tab::make('Categoría 1')
    //             ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('categories', function (Builder $query) {
    //                 $query->where('categories.id', 1); // Filtra por categoría con ID 1
    //             })),
    //         'cat_2' => Tab::make('Categoría 2')
    //             ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('categories', function (Builder $query) {
    //                 $query->where('categories.id', 2); // Filtra por categoría con ID 2
    //             })),
    //     ];
    // }
}
