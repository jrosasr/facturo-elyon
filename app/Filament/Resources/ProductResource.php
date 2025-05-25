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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query)  {
                $query->where('user_id', auth()->user()->id);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_min')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('category_id')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
