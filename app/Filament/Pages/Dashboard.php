<?php

namespace App\Filament\Pages;

use App\Models\Cycle;
use App\Models\CycleItem;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?int $navigationSort = 1;
    
    public function getTitle(): string
    {
        return '';
    }

    public function getPinnedCycleItems(): Collection
    {
        return CycleItem::query()
            ->whereHas('cycle', fn ($query) => $query->where('user_id', Auth::id()))
            ->where('is_pinned', true)
            ->latest('pinned_at')
            ->limit(6)
            ->get();
    }

    public function getDecks(): Collection
    {
        return Cycle::query()
            ->where('user_id', Auth::id())
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get();
    }
}
