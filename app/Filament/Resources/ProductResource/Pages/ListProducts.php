<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use App\Models\Category;
use App\Models\Product;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todos')
                ->badge(Product::where('user_id', auth()->user()->id)->count()), // Cuenta todos los productos del usuario
        ];

        Category::all()->each(function (Category $category) use (&$tabs) {
            $tabs[$category->id] = Tab::make($category->name)
                ->badge(Product::where('user_id', auth()->user()->id)
                    ->whereHas('categories', function (Builder $q) use ($category) {
                        $q->where('categories.id', $category->id);
                    })
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('categories', function (Builder $q) use ($category) {
                    $q->where('categories.id', $category->id);
                }));
        });

        return $tabs;
    }

}
