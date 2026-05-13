<?php

namespace App\Filament\Pages;

use App\Models\Trigger;
use App\Models\TriggerGroup;
use App\Services\PlanLimitService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TriggerManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.trigger-manager';
    
    protected static string | \BackedEnum | null $navigationIcon = 'icon-hook-icon';
    protected static string | \UnitEnum | null $navigationGroup = 'Planeador';
    protected static ?string $title = 'Triggers';
    protected static ?string $navigationLabel = 'Triggers';
    protected static ?int $navigationSort = 1;
    
    public string $activeTab = 'groups';
    public string $groupSortDirection = 'desc';
    
    public ?int $selectedGroupId = null;
    public ?int $expandedTriggerId = null;

    protected function planLimits(): PlanLimitService
    {
        return app(PlanLimitService::class);
    }

    protected function availableTriggersQuery(): Builder
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

    protected function userTriggerGroupsQuery(): Builder
    {
        return TriggerGroup::query()
            ->where('user_id', Auth::id());
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->expandedTriggerId = null;
    }

    public function mount(): void
    {
        $this->selectedGroupId = $this->userTriggerGroupsQuery()
            ->oldest()
            ->value('id');
    }

    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
    }

    public function toggleGroupSort(): void
    {
        $this->groupSortDirection = $this->groupSortDirection === 'desc'
            ? 'asc'
            : 'desc';

        unset($this->triggerGroups);
    }
    
    public function getTriggerGroupsProperty(): Collection
    {
        return $this->userTriggerGroupsQuery()
            ->withCount('triggers')
            ->orderBy('created_at', $this->groupSortDirection)
            ->get();
    }

    public function getSelectedGroupProperty(): ?TriggerGroup
    {
        if (! $this->selectedGroupId) {
            return null;
        }

        return $this->userTriggerGroupsQuery()
            ->with([
                'triggers' => fn ($query) => $query->orderByPivot('sort_order'),
            ])
            ->find($this->selectedGroupId);
    }

    public function toggleTriggerDetails(int $triggerId): void
    {
        $this->expandedTriggerId = $this->expandedTriggerId === $triggerId
            ? null
            : $triggerId;
    }

    public function assignTriggersAction(): Action
    {
        return Action::make('assignTriggers')
            ->label(function (): string {
                $group = $this->selectedGroup;

                if (! $group) {
                    return 'Asignar triggers';
                }

                return $group->triggers()->exists()
                    ? 'Editar grupo'
                    : 'Asignar triggers';
            })
            ->color(function (): string {
                $group = $this->selectedGroup;

                if (! $group) {
                    return 'gray';
                }

                return $group->triggers()->exists()
                    ? 'gray'
                    : 'gray';
            })
            ->icon('heroicon-o-pencil')
            ->link()
            ->modalHeading(function (): string {
                $group = $this->selectedGroup;

                if (! $group) {
                    return 'Asignar triggers';
                }

                return $group->triggers()->exists()
                    ? 'Editar triggers del grupo'
                    : 'Asignar triggers al grupo';
            })
            ->disabled(fn () => ! $this->selectedGroupId)
            ->fillForm(function (): array {
                $group = $this->selectedGroup;

                return [
                    'name' => $group?->name,
                    'description' => $group?->description,
                    'trigger_ids' => $group
                        ? $group->triggers()->pluck('triggers.id')->toArray()
                        : [],
                ];
            })
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de grupo')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(2)
                    ->nullable(),
                Select::make('trigger_ids')
                    ->label('Triggers')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return $this->availableTriggersQuery()
                            ->latest()
                            ->get()
                            ->mapWithKeys(function (Trigger $trigger) {
                                return [
                                    $trigger->id => $trigger->title
                                        ?? $trigger->name
                                        ?? $trigger->trigger
                                        ?? 'Trigger #' . $trigger->id,
                                ];
                            })
                            ->toArray();
                    }),
            ])
            ->action(function (array $data): void {
                if (! $this->selectedGroupId) {
                    return;
                }

                $group = $this->userTriggerGroupsQuery()->find($this->selectedGroupId);

                if (! $group) {
                    return;
                }

                TriggerGroup::query()
                    ->whereKey($group->id)
                    ->update([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                    ]);

                $triggerIds = $this->availableTriggersQuery()
                    ->whereIn('id', $data['trigger_ids'] ?? [])
                    ->pluck('id')
                    ->toArray();

                $syncData = collect($triggerIds)
                    ->values()
                    ->mapWithKeys(fn ($triggerId, $index) => [
                        $triggerId => ['sort_order' => $index + 1],
                    ])
                    ->toArray();

                $group->triggers()->sync($syncData);

                $this->expandedTriggerId = null;

                unset($this->selectedGroup);
                unset($this->triggerGroups);
            });
    }

    public function reorderTriggers(array $orderedTriggerIds): void
    {
        if (! $this->selectedGroupId) {
            return;
        }

        $group = $this->userTriggerGroupsQuery()->find($this->selectedGroupId);

        if (! $group) {
            return;
        }

        $allowedTriggerIds = $this->availableTriggersQuery()
            ->whereIn('id', $orderedTriggerIds)
            ->pluck('id')
            ->toArray();

        foreach ($orderedTriggerIds as $index => $triggerId) {
            if (! in_array($triggerId, $allowedTriggerIds)) {
                continue;
            }

            DB::table('trigger_trigger_group')
                ->where('trigger_group_id', $group->id)
                ->where('trigger_id', $triggerId)
                ->update([
                    'sort_order' => $index + 1,
                ]);
        }

        unset($this->selectedGroup);
    }

    public function deleteTriggerGroupAction(): Action
    {
        return Action::make('deleteTriggerGroup')
            ->label('Borrar grupo')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Borrar grupo de triggers')
            ->modalDescription('Esto solo borrará el grupo. Tus triggers seguirán existiendo.')
            ->modalSubmitActionLabel('Si, borrar grupo')
            ->disabled(fn () => ! $this->selectedGroupId)
            ->action(function (): void {
                if (! $this->selectedGroupId) {
                    return;
                }

                $group = $this->userTriggerGroupsQuery()->find($this->selectedGroupId);

                if (! $group) {
                    return;
                }

                TriggerGroup::query()
                    ->whereKey($group->id)
                    ->delete();

                $this->selectedGroupId = $this->userTriggerGroupsQuery()
                    ->latest()
                    ->value('id');

                $this->expandedTriggerId = null;

                unset($this->selectedGroup);
                unset($this->triggerGroups);
            });
    }
    
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('createGroup')
                ->label('Nuevo grupo')
                ->icon('heroicon-o-plus')
                ->model(TriggerGroup::class)
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->columnSpanFull(),
                ])
                ->visible(fn (): bool => $this->activeTab === 'groups')
                ->before(function (CreateAction $action): void {
                    $user = Auth::user();

                    if (! $this->planLimits()->canCreateTriggerGroup($user)) {
                        Notification::make()
                            ->title('Llegaste al límite de grupos')
                            ->body('Tu plan Free te permite crear hasta 2 grupos de triggers')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                })
                ->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = Auth::id();

                    return $data;
                })
                ->after(function (TriggerGroup $record): void {
                    $this->selectedGroupId = $record->id;

                    unset($this->selectedGroup);
                    unset($this->triggerGroups);
                })
        ];
    }
}
