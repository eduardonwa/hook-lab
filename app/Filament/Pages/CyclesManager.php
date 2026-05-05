<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CyclesManager extends Page
{
    protected string $view = 'filament.pages.cycles-manager';
    protected static string | \BackedEnum | null $navigationIcon = 'icon-hook-icon';
    protected static string | \UnitEnum | null $navigationGroup = 'Colección';
    
    protected static ?string $navigationLabel = 'Barajas';
    protected static ?string $title = 'Barajas';    
    protected static ?int $navigationSort = 2;
}