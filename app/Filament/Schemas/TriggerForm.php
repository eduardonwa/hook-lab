<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TriggerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre')
                ->required(),

            Textarea::make('description')
                ->label('Descripción')
                ->autosize(),

            Select::make('access_level')
                ->label('Plan'),

            Toggle::make('is_active')
                ->label('Activo')
        ];
    }
}