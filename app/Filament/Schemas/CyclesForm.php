<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CyclesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre'),
            DateTimePicker:: make('created_at')
                ->label('Generado')
                ->hint('DD/MM/AAAA')
                ->native(false)
                ->displayFormat('d / m / Y — h:i A')
                ->seconds(false),
            Toggle::make('is_active')
                ->label(fn ($state) => $state ? 'Activado' : 'Desactivado')
                ->required(),
        ];
    }
}