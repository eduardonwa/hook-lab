<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CyclesManager;
use App\Models\Cycle;
use App\Models\CycleItem;
use App\Models\Idea;
use App\Services\PlanLimitService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    public int $bagHooksCount = 0;
    public ?int $editingItemId = null;
    public ?string $editingHookName = null;
    public ?string $editingHookDescription = null;

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
        $this->bagHooksCount = $this->cycle->bagHooks()->count();
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
            ->with(['hook', 'idea'])
            ->orderBy('position', 'asc')
            ->get();
    }

    public function editItemAction(): Action
    {
        return Action::make('editItem')
            ->label('Editar combo')
            ->size('sm')
            ->icon('heroicon-o-adjustments-horizontal')
            ->modalWidth(Width::Large)
            ->modalHeading('Editar combinación')
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $this->editingItemId = (int) $arguments['item_id'];

                $item = CycleItem::query()
                    ->where('cycle_id', $this->cycle->id)
                    ->with('hook')
                    ->findOrFail($this->editingItemId);

                $this->editingHookName = $item->hook?->name;
                $this->editingHookDescription = $item->hook?->description;

                $schema->fill([
                    'idea_id' => $item->idea_id,
                ]);
            })
            ->schema([
                TextEntry::make('current_hook_name')
                    ->label('Hook')
                    ->color('gray')
                    ->state(function () {
                        if (! $this->editingItemId) {
                            return '-';
                        }

                        $item = CycleItem::query()
                            ->where('cycle_id', $this->cycle->id)
                            ->with('hook')
                            ->find($this->editingItemId);

                        return $item?->hook?->name ?? '-';
                    }),

                TextEntry::make('current_hook_description')
                    ->label('Descripción')
                    ->color('gray')
                    ->state(function () {
                        if (! $this->editingItemId) {
                            return '-';
                        }

                        $item = CycleItem::query()
                            ->where('cycle_id', $this->cycle->id)
                            ->with('hook')
                            ->find($this->editingItemId);

                        return $item?->hook?->description ?? '-';
                    }),

                Select::make('idea_id')
                    ->label('Idea')
                    ->options(function () {
                        if (! $this->editingItemId) {
                            return [];
                        }

                        $item = CycleItem::query()
                            ->where('cycle_id', $this->cycle->id)
                            ->findOrFail($this->editingItemId);

                        return Idea::query()
                            ->where('hook_id', $item->hook_id)
                            ->orderBy('title')
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->createOptionForm([
                        Grid::make()
                            ->columns([
                                'default' => 1,
                                'md' => 2
                            ])
                            ->schema([
                                TextEntry::make('create_hook_name')
                                    ->label('Hook')
                                    ->state(fn () => $this->editingHookName ?? '-'),
        
                                TextEntry::make('create_hook_description')
                                    ->label('Descripción')
                                    ->state(fn () => $this->editingHookDescription ?? '-'),
                            ]),
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3),
                    ])
                    ->createOptionUsing(function (array $data) {
                        if (! $this->editingItemId) {
                            return null;
                        }

                        $item = CycleItem::query()
                            ->where('cycle_id', $this->cycle->id)
                            ->findOrFail($this->editingItemId);

                        return Idea::create([
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                            'hook_id' => $item->hook_id,
                        ])->id;
                    })
                    ->extraAttributes([
                        'class' => 'idea-select-with-create',
                    ])
            ])
            ->action(function (array $data): void {
                if (! $this->editingItemId) {
                    return;
                }

                $item = CycleItem::query()
                    ->where('cycle_id', $this->cycle->id)
                    ->findOrFail($this->editingItemId);

                $item->update([
                    'idea_id' => $data['idea_id'] ?? null,
                ]);

                $this->editingItemId = null;
                
                $this->refreshCycle();
                
                $this->dispatch('$refresh');
            });
    }

    public function editIdeaAction(): Action
    {
        return Action::make('editIdea')
            ->label('Editar idea')
            ->icon('heroicon-o-pencil-square')
            ->size('sm')
            ->modalHeading('Editar idea')
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $this->editingItemId = (int) $arguments['item_id'];

                $item = CycleItem::query()
                    ->where('cycle_id', $this->cycle->id)
                    ->with('idea')
                    ->findOrFail($this->editingItemId);

                $schema->fill([
                    'title' => $item->idea?->title,
                    'description' => $item->idea?->description,
                ]);
            })
            ->schema([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                if (! $this->editingItemId) {
                    return;
                }

                $item = CycleItem::query()
                    ->where('cycle_id', $this->cycle->id)
                    ->with('idea')
                    ->findOrFail($this->editingItemId);

                if (! $item->idea) {
                    return;
                }

                $item->idea->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                ]);

                $this->editingItemId = null;

                $this->refreshCycle();

                $this->dispatch('$refresh');
            })
            ->slideOver();
    }

    protected function addHooksToCycleFromBag(array $hookIds): void
    {
        DB::transaction(function () use ($hookIds) {
            $hookIds = collect($hookIds)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($hookIds)) {
                return;
            }

            $availableHookIds = $this->cycle
                ->bagHooks()
                ->whereIn('hooks.id', $hookIds)
                ->pluck('hooks.id')
                ->all();

            $availableHookIds = collect($availableHookIds)
                ->sortBy(fn ($hookId) => array_search($hookId, $hookIds))
                ->values()
                ->all();

            if (empty($availableHookIds)) {
                return;
            }

            $nextPosition = ((int) $this->cycle->items()->max('position')) + 1;

            foreach ($availableHookIds as $index => $hookId) {
                $this->cycle->items()->create([
                    'hook_id' => $hookId,
                    'idea_id' => null,
                    'position' => $nextPosition + $index,
                ]);
            }

            $this->cycle->bagHooks()->detach($availableHookIds);

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
            ->modalWidth(Width::Medium)
            ->modalHeading('Agregar hooks desde la bolsa')
            ->modalSubmitActionLabel('Agregar')
            ->modalCancelActionLabel('Cancelar')
            ->schema([
                Radio::make('bag_mode')
                    ->label('¿Cómo quieres agregar?')
                    ->options([
                        'random' => 'Sacar hooks al azar',
                        'manual' => 'Elegir hooks',
                    ])
                    ->default('random')
                    ->required()
                    ->live(),

                TextInput::make('random_count')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn () => $this->bagHooksCount)
                    ->default(1)
                    ->visible(fn ($get) => $get('bag_mode') === 'random')
                    ->required(fn ($get) => $get('bag_mode') === 'random'),

                Select::make('hook_ids')
                    ->label('Hooks disponibles')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => $this->cycle
                        ->fresh()
                        ->bagHooks()
                        ->orderBy('name')
                        ->pluck('name', 'hooks.id')
                        ->toArray()
                    )
                    ->visible(fn ($get) => $get('bag_mode') === 'manual')
                    ->required(fn ($get) => $get('bag_mode') === 'manual'),
            ])
            ->action(function (array $data): void {
                if ($data['bag_mode'] === 'random') {
                    $hookIds = $this->cycle
                        ->fresh()
                        ->bagHooks()
                        ->inRandomOrder()
                        ->limit((int) $data['random_count'])
                        ->pluck('hooks.id')
                        ->all();

                    $this->addHooksToCycleFromBag($hookIds);
                }

                if ($data['bag_mode'] === 'manual') {
                    $this->addHooksToCycleFromBag($data['hook_ids'] ?? []);
                }

                $this->refreshCycle();

                $this->dispatch('$refresh');
            });
    }

    public function removeItemAction(): Action
    {
        return Action::make('removeItem')
            ->label('Quitar carta')
            ->icon('heroicon-o-trash')
            ->size('sm')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Quitar carta de la baraja')
            ->modalDescription('El hook volverá a la bolsa para que puedas usarlo después.')
            ->action(function (array $arguments): void {
                DB::transaction(function () use ($arguments) {
                    $item = CycleItem::query()
                        ->where('cycle_id', $this->cycle->id)
                        ->findOrFail((int) $arguments['item_id']);

                    $hookId = $item->hook_id;

                    $item->delete();

                    $this->cycle->bagHooks()->syncWithoutDetaching([
                        $hookId => [
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
                    ->body('Tu plan Free permite fijar hasta 8 combos.')
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
}
