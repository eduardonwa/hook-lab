<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\QuickHookGenerator;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Inicio';

    public function getTitle(): string
    {
        return '';
    }

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [
            QuickHookGenerator::class
        ];
    }
}
