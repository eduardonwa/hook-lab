<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CycleBoard;
use App\Models\Hook;
use App\Models\HookGroup;
use App\Services\CycleNameGenerator;
use App\Services\PlanLimitService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CyclesManager extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.pages.cycles-manager';

    protected static string | \BackedEnum | null $navigationIcon = 'icon-deck-icon';

    protected static string | \UnitEnum | null $navigationGroup = 'Colección';

    protected static ?string $navigationLabel = 'Barajas';

    protected static ?string $title = 'Barajas';

    protected static ?int $navigationSort = 2;

    public function createCycleAction(): Action
    {
        return Action::make('createCycle')
            ->label('Nueva baraja')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalWidth(Width::Medium)
            ->modalHeading('')
            ->modalSubmitActionLabel('Nueva baraja')
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->default(fn () => CycleNameGenerator::generateUnique())
                    ->required()
                    ->maxLength(255),

                Radio::make('start_mode')
                    ->label('¿Cómo quieres comenzar?')
                    ->options(function () {
                        $hasHooks = $this->availableHooksQuery()->exists();

                        return $hasHooks ? [
                            'random_hooks' => 'Al azar',
                            'group_hooks' => 'Cargar grupo',
                        ] : [];
                    })
                    ->default('random_hooks')
                    ->required()
                    ->live(),

                TextInput::make('random_hooks_count')
                    ->label('Empezar con')
                    ->suffix('hooks al azar')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn () => $this->maxCombosPerCycle())
                    ->default(fn () => $this->maxCombosPerCycle())
                    ->disabled(fn () => $this->availableHooksQuery()->count() === 0)
                    ->live()
                    ->helperText(function ($get) {
                        $totalHooks = $this->maxCombosPerCycle();
                        $count = (int) ($get('random_hooks_count') ?? 0);

                        if ($totalHooks === 0) {
                            return 'No hay hooks disponibles todavía.';
                        }

                        if ($count >= $totalHooks) {
                            return "Se llenará esta baraja con {$totalHooks} combos.";
                        }

                        $remaining = $totalHooks - $count;

                        return "{$remaining} espacios quedarán libres en esta baraja.";
                    })
                    ->visible(fn ($get) => $get('start_mode') === 'random_hooks')
                    ->required(fn ($get) => $get('start_mode') === 'random_hooks'),

                Select::make('hook_group_ids')
                    ->label('Grupos de hooks')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => HookGroup::query()
                        ->whereHas('hooks', function ($query) {
                            if (! Auth::user()->isPro()) {
                                $query->where('access_level', 'free');
                            }
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->visible(fn ($get) => $get('start_mode') === 'group_hooks')
                    ->required(fn ($get) => $get('start_mode') === 'group_hooks'),
            ])
            ->action(function (array $data): void {
                $user = Auth::user();

                /** @var PlanLimitService $limits */
                $limits = app(PlanLimitService::class);

                if (! $limits->canCreateDeck($user)) {
                    $maxDecks = $limits->limit($user, 'max_decks');

                    Notification::make()
                        ->title('Límite alcanzado')
                        ->body("Tu plan permite crear hasta {$maxDecks} barajas.")
                        ->warning()
                        ->send();

                    return;
                }

                DB::transaction(function () use ($data, $user, $limits) {
                    $user->cycles()->update([
                        'is_active' => false,
                    ]);

                    $selectedHookIds = [];

                    if ($data['start_mode'] === 'random_hooks') {
                        $totalHooks = $this->availableHooksQuery()->count();

                        $count = min(
                            (int) ($data['random_hooks_count'] ?? 1),
                            $totalHooks,
                        );

                        $selectedHookIds = $this->availableHooksQuery()
                            ->inRandomOrder()
                            ->limit($count)
                            ->pluck('id')
                            ->all();
                    }

                    if ($data['start_mode'] === 'group_hooks') {
                        $selectedHookIds = $this->availableHooksQuery()
                            ->join('hook_hook_group', 'hooks.id', '=', 'hook_hook_group.hook_id')
                            ->whereIn('hook_hook_group.hook_group_id', $data['hook_group_ids'] ?? [])
                            ->orderByDesc('hook_hook_group.created_at')
                            ->orderByDesc('hook_hook_group.id')
                            ->select('hooks.id')
                            ->pluck('hooks.id')
                            ->all();
                    }

                    $selectedHookIds = collect($selectedHookIds)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $maxCombosPerDeck = $limits->limit($user, 'max_combos_per_deck');

                    if (! is_null($maxCombosPerDeck)) {
                        $selectedHookIds = collect($selectedHookIds)
                            ->take($maxCombosPerDeck)
                            ->values()
                            ->all();
                    }

                    $cycle = $user->cycles()->create([
                        'name' => $data['name'],
                        'generation_mode' => match ($data['start_mode']) {
                            'random_hooks' => 'random',
                            'group_hooks' => 'group',
                            default => 'random',
                        },
                        'size' => count($selectedHookIds),
                        'is_active' => true,
                    ]);

                    if (! empty($selectedHookIds)) {
                        $hooks = Hook::query()
                            ->whereIn('id', $selectedHookIds)
                            ->get()
                            ->sortBy(fn ($hook) => array_search($hook->id, $selectedHookIds))
                            ->values();

                        foreach ($hooks as $index => $hook) {
                            $cycle->items()->create([
                                'hook_id' => $hook->id,
                                'position' => $index + 1,
                                'idea_id' => null,
                            ]);
                        }
                    }

                    $remainingHookIds = $this->availableHooksQuery()
                        ->whereNotIn('id', $selectedHookIds)
                        ->pluck('id')
                        ->all();

                    if (! empty($remainingHookIds)) {
                        $now = now();

                        DB::table('cycle_hook_bag')->insert(
                            collect($remainingHookIds)
                                ->map(fn ($hookId) => [
                                    'cycle_id' => $cycle->id,
                                    'hook_id' => $hookId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ])
                                ->all()
                        );
                    }
                });

                $this->dispatch('$refresh');
            });
    }

    public function viewCycleAction(): Action
    {
        return Action::make('viewCycle')
            ->label('Ver baraja')
            ->icon('heroicon-o-eye')
            ->modalHeading(function (array $arguments): string {
                $cycle = Auth::user()
                    ->cycles()
                    ->find($arguments['cycle_id']);

                return $cycle?->name ?? 'Baraja';
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->extraAttributes([
                'class' => 'ml-auto',
            ])
            ->modalContent(function (array $arguments) {
                $cycle = Auth::user()
                    ->cycles()
                    ->with([
                        'items.hook',
                        'items.idea',
                    ])
                    ->findOrFail($arguments['cycle_id']);

                return view('filament.pages.cycles-manager-view-cycle-modal', [
                    'cycle' => $cycle,
                ]);
            })
            ->extraModalFooterActions(function (array $arguments): array {
                return [
                    Action::make('editCycle')
                        ->label('Editar baraja')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->extraAttributes([
                            'class' => 'ml-auto',
                        ])
                        ->url(fn () => CycleBoard::getUrl([
                            'cycle' => $arguments['cycle_id']
                        ]))
                ];
            });
    }

    public function getCyclesProperty()
    {
        return Auth::user()
            ->cycles()
            ->withCount(['items', 'bagHooks'])
            ->latest()
            ->get();
    }

    public function removeCycleAction(): Action
    {
        return Action::make('removeCycle')
            ->label('Eliminar baraja')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Eliminar baraja')
            ->modalDescription('Esto eliminará la baraja, sus cartas y su bolsa. Esta acción no se puede deshacer.')
            ->modalSubmitActionLabel('Eliminar')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (array $arguments): void {
                $cycle = Auth::user()
                    ->cycles()
                    ->findOrFail((int) $arguments['cycle_id']);

                $cycle->delete();

                $this->dispatch('$refresh');
            });
    }

    protected function availableHooksQuery()
    {
        $user = Auth::user();

        return Hook::query()
            ->where(function ($query) use ($user) {
                $query
                    ->where(function ($query) use ($user) {
                        $query->whereNull('user_id');

                        if ($user->isPro()) {
                            $query->whereIn('access_level', ['free', 'pro']);
                        } else {
                            $query->where('access_level', 'free');
                        }
                    })
                    ->orWhere('user_id', $user->id);
            });
    }

    protected function maxCombosPerCycle(): int
    {
        $user = Auth::user();

        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        $availableHooksCount = $this->availableHooksQuery()->count();

        $planLimit = $limits->limit($user, 'max_combos_per_deck');

        if (is_null($planLimit)) {
            return $availableHooksCount;
        }

        return min($availableHooksCount, $planLimit);
    }
}