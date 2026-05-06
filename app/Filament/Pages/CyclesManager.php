<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CycleBoard;
use App\Models\Cycle;
use App\Models\Hook;
use App\Models\HookGroup;
use App\Services\CycleNameGenerator;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class CyclesManager extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.pages.cycles-manager';

    protected static string | \BackedEnum | null $navigationIcon = 'icon-hook-icon';

    protected static string | \UnitEnum | null $navigationGroup = 'Colección';

    protected static ?string $navigationLabel = 'Barajas';

    protected static ?string $title = 'Barajas';

    protected static ?int $navigationSort = 2;

    public function createCycleAction(): Action
    {
        return Action::make('createCycle')
            ->label('Nueva baraja')
            ->icon('heroicon-o-rectangle-stack')
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
                        $hasHooks = Hook::query()->exists();

                        return [
                            'empty' => 'En blanco',
                            ...($hasHooks ? [
                                'full' => 'Con todos los hooks',
                                'random_hooks' => 'Empezar con hooks al azar',
                                'group_hooks' => 'Cargar grupo',
                            ] : []),
                        ];
                    })
                    ->default('empty')
                    ->required()
                    ->live(),

                TextInput::make('random_hooks_count')
                    ->label('Empezar con')
                    ->suffix('hooks al azar')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn () => Hook::query()->count())
                    ->default(fn () => min(34, Hook::query()->count()))
                    ->disabled(fn () => Hook::query()->count() === 0)
                    ->live()
                    ->helperText(function ($get) {
                        $totalHooks = Hook::query()->count();
                        $count = (int) ($get('random_hooks_count') ?? 0);

                        if ($totalHooks === 0) {
                            return 'No hay hooks disponibles todavía.';
                        }

                        if ($count >= $totalHooks) {
                            return 'Se usarán todos tus hooks.';
                        }

                        $remaining = $totalHooks - $count;

                        return "{$remaining} hooks quedarán en la bolsa.";
                    })
                    ->visible(fn ($get) => $get('start_mode') === 'random_hooks')
                    ->required(fn ($get) => $get('start_mode') === 'random_hooks'),

                Select::make('hook_group_ids')
                    ->label('Grupos de hooks')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => HookGroup::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->visible(fn ($get) => $get('start_mode') === 'group_hooks')
                    ->required(fn ($get) => $get('start_mode') === 'group_hooks'),
            ])
            ->action(function (array $data): void {
                DB::transaction(function () use ($data) {
                    Cycle::query()->update([
                        'is_active' => false,
                    ]);

                    $selectedHookIds = [];

                    if ($data['start_mode'] === 'random_hooks') {
                        $totalHooks = Hook::query()->count();

                        $count = min(
                            (int) ($data['random_hooks_count'] ?? 1),
                            $totalHooks,
                        );

                        $selectedHookIds = Hook::query()
                            ->inRandomOrder()
                            ->limit($count)
                            ->pluck('id')
                            ->all();
                    }

                    if ($data['start_mode'] === 'group_hooks') {
                        $selectedHookIds = Hook::query()
                            ->whereHas('groups', function ($query) use ($data) {
                                $query->whereIn('hook_groups.id', $data['hook_group_ids'] ?? []);
                            })
                            ->orderBy('id')
                            ->pluck('id')
                            ->all();
                    }

                    if ($data['start_mode'] === 'full') {
                        $selectedHookIds = Hook::query()
                            ->orderBy('id')
                            ->pluck('id')
                            ->all();
                    }

                    $selectedHookIds = collect($selectedHookIds)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $cycle = Cycle::create([
                        'name' => $data['name'],
                        'generation_mode' => match ($data['start_mode']) {
                            'random_hooks' => 'azar',
                            'group_hooks' => 'group',
                            'full' => 'full',
                            default => 'empty',
                        },
                        'size' => count($selectedHookIds),
                        'is_active' => true,
                    ]);

                    if (count($selectedHookIds)) {
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

                    if ($data['start_mode'] !== 'full') {
                        $remainingHookIds = Hook::query()
                            ->whereNotIn('id', $selectedHookIds)
                            ->pluck('id')
                            ->all();

                        if (count($remainingHookIds)) {
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
                $cycle = Cycle::find($arguments['cycle_id']);

                return $cycle?->name ?? 'Baraja';
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->extraAttributes([
                'class' => 'ml-auto',
            ])
            ->modalContent(function (array $arguments) {
                $cycle = Cycle::query()
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
        return Cycle::withCount(['items', 'bagHooks'])
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
                $cycle = Cycle::query()->findOrFail((int) $arguments['cycle_id']);

                $cycle->delete();

                $this->dispatch('$refresh');
            });
    }
}