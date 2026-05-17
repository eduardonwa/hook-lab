<?php

namespace App\Filament\Pages;

use App\Models\Cycle;
use App\Models\CycleItem;
use App\Models\Trigger;
use App\Services\CycleNameGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Inicio';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return '';
    }

    protected function pinnedCycleItemsQuery(): Builder
    {
        return CycleItem::query()
            ->with(['cycle', 'hook', 'trigger'])
            ->whereHas('cycle', fn ($query) => $query->where('user_id', Auth::id()))
            ->where('is_pinned', true)
            ->latest('pinned_at');
    }

    public function getPinnedCycleItems(): Collection
    {
        return $this->pinnedCycleItemsQuery()
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
                    <p>Tu plan Free permite crear 2 barajas. <br> Suscríbete a Pro para crear barajas ilimitadas.</p>
                </div>
            '))
            ->modalSubmitActionLabel('Ver planes')
            ->action(function (): void {
                if (! config('services.stripe.billing_enabled')) {
                    Notification::make()
                        ->title('Pro muy pronto')
                        ->body('El plan Pro aún no está activado. Sigue usando Hook Labs en modo Free')
                        ->info()
                        ->send();

                    return;
                }

                // Conectar checkout después
            });
    }

    public function createDeckFromPinnedAction(): Action
    {
        return Action::make('createDeckFromPinned')
            ->label('Crear baraja')
            ->color('primary')
            ->modalHeading(fn (): string => Auth::user()->isPro()
                ? 'Crear baraja desde favoritos'
                : 'Desbloquea barajas desde favoritos')
            ->modalWidth(fn (): Width => Auth::user()->isPro() ? Width::Large : Width::Medium)
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalDescription(null)
            ->modalContent(fn () => Auth::user()->isPro()
                ? null
                : new HtmlString('
                    <div class="space-y-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        <p>Suscríbete a Pro para crear barajas desde tus mejores cartas.</p>
                    </div>
                '))
            ->modalSubmitActionLabel(fn (): string => Auth::user()->isPro() ? 'Crear baraja' : 'Ver planes')
            ->schema(function (): array {
                if (! Auth::user()->isPro()) {
                    return [];
                }

                $pinnedItems = $this->pinnedCycleItemsQuery()->get();

                return [
                    TextInput::make('name')
                        ->label('Nombre')
                        ->default(fn () => CycleNameGenerator::generateUnique())
                        ->required()
                        ->maxLength(255),

                    CheckboxList::make('pinned_item_ids')
                        ->label('Cartas favoritas')
                        ->helperText('Selecciona las cartas que quieres copiar a la nueva baraja.')
                        ->options($pinnedItems->mapWithKeys(fn (CycleItem $item) => [
                            $item->id => $this->formatPinnedItemOption($item),
                        ])->toArray())
                        ->default($pinnedItems->pluck('id')->all())
                        ->bulkToggleable()
                        ->columns(1)
                        ->required(),
                ];
            })
            ->action(function (array $data): void {
                $user = Auth::user();

                if (! $user->isPro()) {
                    if (! config('services.stripe.billing_enabled')) {
                        Notification::make()
                            ->title('Pro muy pronto')
                            ->body('El plan Pro aún no está activado. Sigue usando Hook Labs en modo Free')
                            ->info()
                            ->send();

                        return;
                    }

                    // Conectar checkout después
                    return;
                }

                $selectedPinnedItemIds = collect($data['pinned_item_ids'] ?? [])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($selectedPinnedItemIds)) {
                    Notification::make()
                        ->title('Selecciona al menos una carta')
                        ->warning()
                        ->send();

                    return;
                }

                $selectedPinnedItems = $this->pinnedCycleItemsQuery()
                    ->whereIn('cycle_items.id', $selectedPinnedItemIds)
                    ->get()
                    ->sortBy(fn (CycleItem $item) => array_search($item->id, $selectedPinnedItemIds))
                    ->unique('trigger_id')
                    ->values();

                if ($selectedPinnedItems->isEmpty()) {
                    Notification::make()
                        ->title('No se encontraron cartas fijadas')
                        ->warning()
                        ->send();

                    return;
                }

                $cycle = DB::transaction(function () use ($data, $selectedPinnedItems, $user): Cycle {
                    $user->cycles()->update([
                        'is_active' => false,
                    ]);

                    $cycle = $user->cycles()->create([
                        'name' => $data['name'] ?? CycleNameGenerator::generateUnique(),
                        'generation_mode' => 'pinned',
                        'size' => $selectedPinnedItems->count(),
                        'is_active' => true,
                    ]);

                    $selectedTriggerIds = [];

                    foreach ($selectedPinnedItems as $index => $item) {
                        $cycle->items()->create([
                            'trigger_id' => $item->trigger_id,
                            'hook_id' => $item->hook_id,
                            'hook_text' => $item->hook_text,
                            'idea_text' => $item->idea_text,
                            'note' => $item->note,
                            'position' => $index + 1,
                            'board_state' => CycleItem::BOARD_STATE_DECK,
                            'is_pinned' => false,
                            'pinned_at' => null,
                        ]);

                        $selectedTriggerIds[] = $item->trigger_id;
                    }

                    $remainingTriggerIds = Trigger::query()
                        ->where('is_active', true)
                        ->whereIn('access_level', ['free', 'pro'])
                        ->whereNotIn('id', $selectedTriggerIds)
                        ->pluck('id')
                        ->all();

                    if (! empty($remainingTriggerIds)) {
                        $now = now();

                        DB::table('cycle_trigger_bag')->insert(
                            collect($remainingTriggerIds)
                                ->map(fn ($triggerId) => [
                                    'cycle_id' => $cycle->id,
                                    'trigger_id' => $triggerId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ])
                                ->all()
                        );
                    }

                    return $cycle;
                });

                Notification::make()
                    ->title('Baraja creada')
                    ->success()
                    ->send();

                $this->redirect(CycleBoard::getUrl(['cycle' => $cycle->id]));
            });
    }

    private function formatPinnedItemOption(CycleItem $item): string
    {
        $parts = [
            $item->trigger?->name ?? 'Sin trigger',
        ];

        if (filled($item->hook_text)) {
            $parts[] = str($item->hook_text)->limit(48)->toString();
        }

        if (filled($item->idea_text)) {
            $parts[] = str($item->idea_text)->limit(64)->toString();
        }

        return implode(' · ', $parts);
    }
}
