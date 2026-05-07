<?php

namespace App\Filament\Pages;

use App\Models\Cycle;
use App\Models\CycleItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
            ->with(['cycle', 'hook', 'idea'])
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

    public function subscribeAction(): Action
    {
        return Action::make('subscribe')
            ->label('¿Necesitas más?')
            ->color('primary')
            ->modalHeading('Desbloquea más barajas')
            ->modalWidth(Width::Medium)
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalDescription(null)
            ->modalContent(new HtmlString('
                <div class="space-y-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>Tu plan Free permite crear 1 baraja. <br> Suscríbete a Pro para crear barajas ilimitadas.</p>
                </div>
            '))
            ->modalSubmitActionLabel('Ver planes')
            ->action(function (): void {
                // $this->redirect(route('filament.admin.pages.billing'));
            });
    }
}
