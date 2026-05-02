<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IdeaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Select::make('hook_id')
                ->relationship('hook', 'name')
                ->required(),
            TextInput::make('title')
                ->label('Título')
                ->required(),
            Textarea::make('description')
                ->label('Descripción')
                ->columnSpanFull(),
        ];
    }
}