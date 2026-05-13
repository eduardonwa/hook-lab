<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CyclesManager;
use App\Models\Cycle;
use App\Models\CycleItem;
use App\Services\PlanLimitService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CycleBoard extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.pages.cycle-board';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'barajas/{cycle}/board';
    protected static ?string $title = '';
    public string $viewMode = 'cards';

    public Cycle $cycle;
    public int $itemsCount = 0;
    public int $bagTriggersCount = 0;
    public ?int $editingItemId = null;
    public ?string $editingTriggerName = null;
    public ?string $editingTriggerDescription = null;

    public function mount(Cycle $cycle): void
    {
        abort_unless($cycle->user_id === Auth::id(), 403);

        $this->cycle = $cycle;

        $this->refreshCycle();
    }

    protected function refreshCycle(): void
    {
        $this->cycle = $this->cycle->fresh();

        $this->itemsCount = $this->cycle->items()->count();
        $this->bagTriggersCount = $this->cycle->bagTriggers()->count();
    }

    public function getBreadcrumbs(): array
    {
        return [
            CyclesManager::getUrl() => 'Barajas',
            $this->cycle->name,
        ];
    }

    public function getItemsProperty()
    {
        return $this->cycle
            ->items()
            ->with(['trigger', 'hook'])
            ->orderBy('position', 'asc')
            ->get();
    }

    public function editCardAction(): Action
    {
        return Action::make('editCard')
            ->label('Editar carta')
            ->size('sm')
            ->icon('heroicon-o-adjustments-horizontal')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Guardar carta')
            ->modalCancelActionLabel('Cancelar')
            ->mountUsing(function (array $arguments): void {
                $this->editingItemId = (int) $arguments['item_id'];

                $item = CycleItem::query()
                    ->where('cycle_id', $this->cycle->id)
                    ->with('trigger')
                    ->findOrFail($this->editingItemId);

                $this->editingTriggerName = $item->trigger?->name;
                $this->editingTriggerDescription = $item->trigger?->description;
            })
            ->schema([
                TextEntry::make('current_trigger_name')
                    ->label('Trigger')
                    ->color('gray')
                    ->state(function () {
                        if (! $this->editingItemId) {
                            return '-';
                        }

                        $item = CycleItem::query()
                            ->where('cycle_id', $this->cycle->id)
                            ->with('trigger')
                            ->find($this->editingItemId);

                        return $item?->trigger?->name ?? '-';
                    }),

                TextEntry::make('current_trigger_description')
                    ->label('Descripción')
                    ->color('gray')
                    ->state(function () {
                        if (! $this->editingItemId) {
                            return '-';
                        }

                        $item = CycleItem::query()
                            ->where('cycle_id', $this->cycle->id)
                            ->with('trigger')
                            ->find($this->editingItemId);

                        $description = $item->trigger?->description;

                        if (blank($description)) {
                            return '-';
                        }

                        return str($description)
                            ->replace(["\r\n", "\r"], "\n")
                            ->trim()
                            ->replaceMatches("/\n{3,}/", "\n\n")
                            ->toString();
                    })
                    ->extraAttributes([
                        'class' => 'whitespace-pre-line',
                    ]),
            ])
            ->action(function (): void {
                if (! $this->editingItemId) {
                    return;
                }

                CycleItem::query()
                    ->where('cycle_id', $this->cycle->id)
                    ->findOrFail($this->editingItemId);

                $this->editingItemId = null;
                
                $this->refreshCycle();
                
                $this->dispatch('$refresh');
            });
    }

    protected function addTriggersToCycleFromBag(array $triggerIds): void
    {
        DB::transaction(function () use ($triggerIds) {
            $triggerIds = collect($triggerIds)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($triggerIds)) {
                return;
            }

            $limit = $this->comboLimit();

            if (! is_null($limit)) {
                $currentCount = $this->cycle->items()->count();
                $remainingSlots = max(0, $limit - $currentCount);

                if ($remainingSlots === 0) {
                    Notification::make()
                        ->title('Límite alcanzado')
                        ->body("Tu plan Free permite hasta {$limit} cartas por baraja.")
                        ->warning()
                        ->send();

                    return;
                }

                $triggerIds = collect($triggerIds)
                    ->take($remainingSlots)
                    ->values()
                    ->all();
            }

            $availableTriggerIds = $this->cycle
                ->bagTriggers()
                ->whereIn('triggers.id', $triggerIds)
                ->pluck('triggers.id')
                ->all();

            $availableTriggerIds = collect($availableTriggerIds)
                ->sortBy(fn ($triggerId) => array_search($triggerId, $triggerIds))
                ->values()
                ->all();

            if (empty($availableTriggerIds)) {
                return;
            }

            $nextPosition = ((int) $this->cycle->items()->max('position')) + 1;

            foreach ($availableTriggerIds as $index => $triggerId) {
                $this->cycle->items()->create([
                    'trigger_id' => $triggerId,
                    'position' => $nextPosition + $index,
                ]);
            }

            $this->cycle->bagTriggers()->detach($availableTriggerIds);

            $this->cycle->update([
                'size' => $this->cycle->items()->count(),
            ]);
        });

        $this->refreshCycle();
    }

    public function addFromBagAction(): Action
    {
        return Action::make('addFromBag')
            ->label('Agregar desde bolsa')
            ->icon('heroicon-o-plus-circle')
            ->color('gray')
            ->visible(fn (): bool => $this->canAddMoreCombos())
            ->modalWidth(Width::Medium)
            ->modalHeading('Agregar triggers desde la bolsa')
            ->modalSubmitActionLabel('Agregar')
            ->modalCancelActionLabel('Cancelar')
            ->schema([
                Radio::make('bag_mode')
                    ->label('¿Cómo quieres agregar un trigger nuevo?')
                    ->options([
                        'random' => 'Al azar',
                        'manual' => 'Elegir',
                    ])
                    ->default('random')
                    ->required()
                    ->live(),

                TextInput::make('random_count')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue($this->bagTriggersCount)
                    ->default(1)
                    ->visible(fn ($get) => $get('bag_mode') === 'random')
                    ->required(fn ($get) => $get('bag_mode') === 'random'),

                Select::make('trigger_ids')
                    ->label('Triggers disponibles')
                    ->multiple()
                    ->maxItems(fn () => $this->remainingComboSlots())
                    ->searchable()
                    ->preload()
                    ->options(fn () => $this->cycle
                        ->fresh()
                        ->bagTriggers()
                        ->orderBy('name')
                        ->pluck('name', 'triggers.id')
                        ->toArray()
                    )
                    ->visible(fn ($get) => $get('bag_mode') === 'manual')
                    ->required(fn ($get) => $get('bag_mode') === 'manual'),
            ])
            ->action(function (array $data): void {
                if ($data['bag_mode'] === 'random') {
                    $triggerIds = $this->cycle
                        ->fresh()
                        ->bagTriggers()
                        ->inRandomOrder()
                        ->limit((int) $data['random_count'])
                        ->pluck('triggers.id')
                        ->all();

                    $this->addTriggersToCycleFromBag($triggerIds);
                }

                if ($data['bag_mode'] === 'manual') {
                    $this->addTriggersToCycleFromBag($data['trigger_ids'] ?? []);
                }

                $this->refreshCycle();

                $this->dispatch('$refresh');
            });
    }

    public function removeCardAction(): Action
    {
        return Action::make('removeCard')
            ->label('Quitar carta')
            ->icon('heroicon-o-trash')
            ->size('sm')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Quitar carta de la baraja')
            ->modalDescription('El trigger volverá a la bolsa para que puedas usarlo después.')
            ->action(function (array $arguments): void {
                DB::transaction(function () use ($arguments) {
                    $item = CycleItem::query()
                        ->where('cycle_id', $this->cycle->id)
                        ->findOrFail((int) $arguments['item_id']);

                    $triggerId = $item->trigger_id;

                    $item->delete();

                    $this->cycle->bagTriggers()->syncWithoutDetaching([
                        $triggerId => [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);

                    $this->cycle
                        ->items()
                        ->orderBy('position')
                        ->get()
                        ->values()
                        ->each(function (CycleItem $item, int $index) {
                            $item->update([
                                'position' => $index + 1,
                            ]);
                        });

                    $this->cycle->update([
                        'size' => $this->cycle->items()->count(),
                    ]);
                });

                $this->refreshCycle();
                $this->dispatch('$refresh');
            });
    }

    public function togglePinItem(int $itemId): void
    {
        $item = CycleItem::query()
            ->where('cycle_id', $this->cycle->id)
            ->findOrFail($itemId);

        if (! $item->is_pinned) {
            $canPin = app(PlanLimitService::class)->canPinMoreItems(Auth::user());

            if (! $canPin) {
                Notification::make()
                    ->title('Llegaste al límite de fijados')
                    ->body('Tu plan Free permite fijar hasta 8 cartas.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $isPinned = ! $item->is_pinned;

        $item->update([
            'is_pinned' => $isPinned,
            'pinned_at' => $isPinned ? now() : null,
        ]);

        $this->refreshCycle();

        $this->dispatch('$refresh');
    }

    public function bringCardToTable(int $itemId): void
    {
        $item = $this->cycle
            ->items()
            ->whereKey($itemId)
            ->firstOrFail();

        $item->update([
            'board_state' => 'table'
        ]);

        $this->refreshCycle();
        $this->dispatch('$refresh');
    }

    public function sendCardToDeck(int $itemId): void
    {
        $item = $this->cycle
            ->items()
            ->whereKey($itemId)
            ->firstOrFail();
        
        $item->update([
            'board_state' => 'deck'
        ]);

        $this->refreshCycle();
        $this->dispatch('$refresh');
    }

    protected function remainingComboSlots(): int
    {
        $limit = $this->comboLimit();

        if (is_null($limit)) {
            return $this->bagTriggersCount;
        }

        return max(0, $limit - $this->cycle->items()->count());
    }

    protected function comboLimit(): ?int
    {
        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        return $limits->limit(Auth::user(), 'max_combos_per_deck');
    }

    public function canAddMoreCombos(): bool
    {
        $limit = $this->comboLimit();

        if (is_null($limit)) {
            return true;
        }

        return $this->cycle->items()->count() < $limit;
    }

    public function hasReachedComboLimit(): bool
    {
        return ! $this->canAddMoreCombos();
    }

    public function addMoreCombosAction(): Action
    {
        return Action::make('addMoreCombos')
            ->label('Agregar más')
            ->color('primary')
            ->visible(fn (): bool => $this->hasReachedComboLimit())
            ->modalHeading('Desbloquea más cartas')
            ->modalWidth(Width::Medium)
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalDescription(null)
            ->modalContent(new HtmlString('
                <div class="space-y-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>Tu plan Free permite hasta 10 cartas por baraja. <br> Suscríbete a Pro para crear barajas sin límite.</p>
                </div>
            '))
            ->modalSubmitActionLabel('Ver planes')
            ->action(function (): void {
                if (! config('services.stripe.billing_enabled')) {
                    Notification::make()
                        ->title('Pro muy pronto')
                        ->body('El plan Pro aún no está activado. Sigue usando Hook Labs en modo Free.')
                        ->info()
                        ->send();

                    return;
                }

                // Conectar checkout después
            });
    }
}