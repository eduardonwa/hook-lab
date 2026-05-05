<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CycleBoard;
use App\Models\Cycle;
use App\Models\Hook;
use App\Services\CycleNameGenerator;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
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
            ->label('Crear baraja')
            ->icon('heroicon-o-rectangle-stack')
            ->color('primary')
            ->modalHeading('Crear baraja')
            ->modalSubmitActionLabel('Crear baraja')
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->default(fn () => CycleNameGenerator::generateUnique())
                    ->required()
                    ->maxLength(255),

                Radio::make('start_mode')
                    ->label('¿Cómo quieres comenzar?')
                    ->options([
                        'empty' => 'En blanco',
                        'random_hooks' => 'Empezar con hooks al azar',
                        'selected_hooks' => 'Sé lo que estoy haciendo',
                    ])
                    ->default('empty')
                    ->required()
                    ->live(),

                TextInput::make('random_hooks_count')
                    ->label('Empezar con')
                    ->helperText('Después podrás seguir sacando hooks de la bolsa.')
                    ->suffix('hooks al azar')
                    ->numeric()
                    ->minValue(1)
                    ->default(34)
                    ->visible(fn ($get) => $get('start_mode') === 'random_hooks')
                    ->required(fn ($get) => $get('start_mode') === 'random_hooks'),

                Select::make('hook_ids')
                    ->label('Hooks')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => Hook::query()->pluck('name', 'id')->toArray())
                    ->visible(fn ($get) => $get('start_mode') === 'selected_hooks'),
            ])
            ->action(function (array $data): void {
                DB::transaction(function () use ($data) {
                    Cycle::query()->update([
                        'is_active' => false,
                    ]);

                    $selectedHookIds = [];

                    if ($data['start_mode'] === 'selected_hooks') {
                        $selectedHookIds = $data['hook_ids'] ?? [];
                    }

                    if ($data['start_mode'] === 'random_hooks') {
                        $selectedHookIds = Hook::query()
                            ->inRandomOrder()
                            ->limit((int) $data['random_hooks_count'])
                            ->pluck('id')
                            ->all();
                    }

                    $cycle = Cycle::create([
                        'name' => $data['name'],
                        'generation_mode' => match ($data['start_mode']) {
                            'random_hooks' => 'azar',
                            'selected_hooks' => 'manual',
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
        return Cycle::withCount('items')
            ->latest()
            ->get();
    }
}