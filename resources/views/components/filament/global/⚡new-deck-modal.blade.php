<?php

use Livewire\Component;
use Livewire\Attributes\On;

use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;

use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;

use App\Models\TriggerGroup;
use App\Services\CycleNameGenerator;
use App\Services\PlanLimitService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Services\Cycles\CreateCycleService;

new class extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[On('open-new-deck-modal')]
    public function openNewDeckModal(): void
    {
        $this->mountAction('createCycle');
    }

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
                try {
                    app(CreateCycleService::class)->create(Auth::user(), $data);

                    Notification::make()
                        ->title('Baraja creada')
                        ->success()
                        ->send();

                    $this->redirect(route('filament.admin.pages.dashboard'));
                } catch (\RuntimeException $e) {
                    if ($e->getMessage() === 'deck_limit_reached') {
                        /** @var PlanLimitService $limits */
                        $limits = app(PlanLimitService::class);

                        $maxDecks = $limits->limit(Auth::user(), 'max_decks');

                        Notification::make()
                            ->title('Límite alcanzado')
                            ->body("Tu plan permite crear hasta {$maxDecks} barajas")
                            ->warning()
                            ->send();

                            return;
                    }
                    throw $e;
                }
            });
    }

    protected function availableTriggersQuery()
    {
        $user = Auth::user();

        return \App\Models\Trigger::query()
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
};

?>

<div>
    <x-filament-actions::modals />
</div>