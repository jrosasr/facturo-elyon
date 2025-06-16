<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Editar organización';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la organización')
                    ->required()
                    ->maxLength(25),
                Forms\Components\TextInput::make('address')
                    ->label('Dirección')
                    ->required()
                    ->maxLength(250),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->required()
                    ->maxLength(50),
                Forms\Components\FileUpload::make('logo')
                    ->required()
                    ->label('Logo')
                    ->directory('team/logos')
                    ->visibility('public')
                    ->image()
                    ->preserveFilenames()
                    ->imagePreviewHeight('150')
                    ->default(function ($record) {
                        return $record?->logo ? [asset('storage/'.$record->logo)] : null;
                    }),
                Forms\Components\Select::make('currency_id')
                    ->label('Moneda')
                    ->relationship('currency', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default(function ($record) {
                        return $record?->currency_id;
                    }),
            ]);
    }
}
