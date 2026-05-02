<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class HookForm
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
                ->required()
                ->unique(ignoreRecord: true)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label('Descripción')
                ->autosize()
                ->columnSpanFull(),
        ];
    }
}