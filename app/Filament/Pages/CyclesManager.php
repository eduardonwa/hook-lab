<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CycleBoard;
use App\Models\Trigger;
use App\Models\TriggerGroup;
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
                        $hasTriggers = $this->availableTriggersQuery()->exists();

                        return $hasTriggers ? [
                            'random_triggers' => 'Sorpréndeme (random)',
                            'group_triggers' => 'Cargar grupo',
                        ] : [];
                    })
                    ->default('random_triggers')
                    ->required()
                    ->live(),

                TextInput::make('random_triggers_count')
                    ->label('Empezar con')
                    ->suffix('triggers al azar')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn () => $this->maxCombosPerCycle())
                    ->default(fn () => $this->maxCombosPerCycle())
                    ->disabled(fn () => $this->availableTriggersQuery()->count() === 0)
                    ->live()
                    ->helperText(function ($get) {
                        $totalTriggers = $this->maxCombosPerCycle();
                        $count = (int) ($get('random_triggers_count') ?? 0);

                        if ($totalTriggers === 0) {
                            return 'No hay triggers disponibles todavía.';
                        }

                        if ($count >= $totalTriggers) {
                            return "Se llenará esta baraja con {$totalTriggers} cartas.";
                        }

                        $remaining = $totalTriggers - $count;

                        return "{$remaining} espacios quedarán libres en esta baraja.";
                    })
                    ->visible(fn ($get) => $get('start_mode') === 'random_triggers')
                    ->required(fn ($get) => $get('start_mode') === 'random_triggers'),

                Select::make('trigger_group_ids')
                    ->label('Grupos de triggers')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => TriggerGroup::query()
                        ->where('user_id', Auth::id())
                        ->whereHas('triggers', function ($query) {
                            $query->whereIn(
                                'access_level',
                                Auth::user()->isPro()
                                    ? ['free', 'pro']
                                    : ['free']
                            );
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->visible(fn ($get) => $get('start_mode') === 'group_triggers')
                    ->required(fn ($get) => $get('start_mode') === 'group_triggers'),
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

                    $selectedTriggerIds = [];

                    if ($data['start_mode'] === 'random_triggers') {
                        $totalTriggers = $this->availableTriggersQuery()->count();

                        $count = min(
                            (int) ($data['random_triggers_count'] ?? 1),
                            $totalTriggers,
                        );

                        $selectedTriggerIds = $this->availableTriggersQuery()
                            ->inRandomOrder()
                            ->limit($count)
                            ->pluck('id')
                            ->all();
                    }

                    if ($data['start_mode'] === 'group_triggers') {
                        $selectedTriggerIds = $this->availableTriggersQuery()
                            ->join('trigger_trigger_group', 'triggers.id', '=', 'trigger_trigger_group.trigger_id')
                            ->whereIn('trigger_trigger_group.trigger_group_id', $data['trigger_group_ids'] ?? [])
                            ->orderBy('trigger_trigger_group.sort_order')
                            ->orderBy('triggers.name')
                            ->select('triggers.id')
                            ->pluck('triggers.id')
                            ->all();
                    }

                    $selectedTriggerIds = collect($selectedTriggerIds)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $maxCombosPerDeck = $limits->limit($user, 'max_combos_per_deck');

                    if (! is_null($maxCombosPerDeck)) {
                        $selectedTriggerIds = collect($selectedTriggerIds)
                            ->take($maxCombosPerDeck)
                            ->values()
                            ->all();
                    }

                    $cycle = $user->cycles()->create([
                        'name' => $data['name'],
                        'generation_mode' => match ($data['start_mode']) {
                            'random_triggers' => 'random',
                            'group_triggers' => 'group',
                            default => 'random',
                        },
                        'size' => count($selectedTriggerIds),
                        'is_active' => true,
                    ]);

                    if (! empty($selectedTriggerIds)) {
                        $triggers = Trigger::query()
                            ->whereIn('id', $selectedTriggerIds)
                            ->get()
                            ->sortBy(fn ($trigger) => array_search($trigger->id, $selectedTriggerIds))
                            ->values();

                        foreach ($triggers as $index => $trigger) {
                            $cycle->items()->create([
                                'trigger_id' => $trigger->id,
                                'hook_id' => null,
                                'hook_text' => null,
                                'idea_text' => null,
                                'position' => $index + 1,
                            ]);
                        }
                    }

                    $remainingTriggerIds = $this->availableTriggersQuery()
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
                        'items.trigger',
                        'items.hook',
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
            ->withCount(['items', 'bagTriggers'])
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

    protected function availableTriggersQuery()
    {
        $user = Auth::user();

        return Trigger::query()
            ->where('is_active', true)
            ->whereIn(
                'access_level',
                $user->isPro()
                    ? ['free', 'pro']
                    : ['free']
            );
    }

    protected function maxCombosPerCycle(): int
    {
        $user = Auth::user();

        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        $availableTriggersCount = $this->availableTriggersQuery()->count();

        $planLimit = $limits->limit($user, 'max_combos_per_deck');

        if (is_null($planLimit)) {
            return $availableTriggersCount;
        }

        return min($availableTriggersCount, $planLimit);
    }
}