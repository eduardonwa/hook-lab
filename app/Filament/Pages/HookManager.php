<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\HookForm;
use App\Models\Hook;
use App\Models\HookGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HookManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.hook-manager';
    
    protected static string | \BackedEnum | null $navigationIcon = 'icon-hook-icon';
    protected static string | \UnitEnum | null $navigationGroup = 'Planeador';
    protected static ?string $title = 'Hooks';
    protected static ?string $navigationLabel = 'Hooks';
    protected static ?int $navigationSort = 1;
    
    public string $activeTab = 'library';
    public string $groupSortDirection = 'desc';
    
    public ?int $selectedGroupId = null;
    public ?int $expandedHookId = null;

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->expandedHookId = null;
    }

    public function mount(): void
    {
        $this->selectedGroupId = HookGroup::query()->oldest()->value('id');
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

        unset($this->hookGroups);
    }
    
    public function getHookGroupsProperty(): Collection
    {
        return HookGroup::query()
            ->withCount('hooks')
            ->orderBy('created_at', $this->groupSortDirection)
            ->get();
    }

    public function getSelectedGroupProperty(): ?HookGroup
    {
        if (! $this->selectedGroupId) {
            return null;
        }

        return HookGroup::query()
            ->with('hooks')
            ->find($this->selectedGroupId);
    }

    public function toggleHookDetails(int $hookId): void
    {
        $this->expandedHookId = $this->expandedHookId === $hookId
            ? null
            : $hookId;
    }

    public function assignHooksAction(): Action
    {
        return Action::make('assignHooks')
            ->label(function (): string {
                $group = $this->selectedGroup;

                if (! $group) {
                    return 'Asignar hooks';
                }

                return $group->hooks()->exists()
                    ? 'Editar grupo'
                    : 'Asignar hooks';
            })
            ->color(function (): string {
                $group = $this->selectedGroup;

                if (! $group) {
                    return 'primary';
                }

                return $group->hooks()->exists()
                    ? 'gray'
                    : 'primary';
            })
            ->icon('heroicon-o-pencil')
            ->modalHeading(function (): string {
                $group = $this->selectedGroup;

                if (! $group) {
                    return 'Asignar hooks';
                }

                return $group->hooks()->exists()
                    ? 'Editar hooks del grupo'
                    : 'Asignar hooks al grupo';
            })
            ->disabled(fn () => ! $this->selectedGroupId)
            ->fillForm(function (): array {
                $group = $this->selectedGroup;

                return [
                    'name' => $group?->name,
                    'description' => $group?->description,
                    'hook_ids' => $group
                        ? $group->hooks()->pluck('hooks.id')->toArray()
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
                Select::make('hook_ids')
                    ->label('Hooks')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return Hook::query()
                            ->latest()
                            ->get()
                            ->mapWithKeys(function (Hook $hook) {
                                return [
                                    $hook->id => $hook->title
                                        ?? $hook->name
                                        ?? $hook->hook
                                        ?? 'Hook #' . $hook->id,
                                ];
                            })
                            ->toArray();
                    }),
            ])
            ->action(function (array $data): void {
                if (! $this->selectedGroupId) {
                    return;
                }

                $group = HookGroup::query()->find($this->selectedGroupId);

                if (! $group) {
                    return;
                }

                HookGroup::query()
                    ->whereKey($group->id)
                    ->update([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                    ]);

                $hookIds = $data['hook_ids'] ?? [];

                $syncData = collect($hookIds)
                    ->values()
                    ->mapWithKeys(fn ($hookId, $index) => [
                        $hookId => ['sort_order' => $index + 1],
                    ])
                    ->toArray();

                $group->hooks()->sync($syncData);

                $this->expandedHookId = null;

                unset($this->selectedGroup);
                unset($this->hookGroups);
            });
    }

    public function reorderHooks(array $orderedHookIds): void
    {
        if (! $this->selectedGroupId) {
            return;
        }

        foreach ($orderedHookIds as $index => $hookId) {
            DB::table('hook_hook_group')
                ->where('hook_group_id', $this->selectedGroupId)
                ->where('hook_id', $hookId)
                ->update([
                    'sort_order' => $index + 1,
                ]);
        }

        unset($this->selectedGroup);
    }

    public function deleteGroupAction(): Action
    {
        return Action::make('deleteGroup')
            ->label('Borrar grupo')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Borrar grupo de hooks')
            ->modalDescription('Esto solo borrará el grupo. Tus hooks seguirán existiendo.')
            ->modalSubmitActionLabel('Si, borrar grupo')
            ->disabled(fn () => ! $this->selectedGroupId)
            ->action(function (): void {
                if (! $this->selectedGroupId) {
                    return;
                }

                $group = HookGroup::query()->find($this->selectedGroupId);

                if (! $group) {
                    return;
                }

                HookGroup::query()
                    ->whereKey($group->id)
                    ->delete();

                $this->selectedGroupId = HookGroup::query()
                    ->latest()
                    ->value('id');

                $this->expandedHookId = null;

                unset($this->selectedGroup);
                unset($this->hookGroups);
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Hook::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Fecha actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordAction('edit')
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Editar hook')
                    ->schema(HookForm::getFormSchema()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('createGroup')
                ->label('Nuevo grupo')
                ->icon('heroicon-o-plus')
                ->model(HookGroup::class)
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
                ->after(function (HookGroup $record): void {
                    $this->selectedGroupId = $record->id;

                    unset($this->selectedGroup);
                    unset($this->hookGroups);
                }),

            CreateAction::make('createHook')
                ->label('Nuevo hook')
                ->icon('heroicon-o-plus')
                ->model(Hook::class)
                ->schema(HookForm::getFormSchema())
                ->visible(fn (): bool => $this->activeTab === 'library')
                ->after(function (): void {
                    $this->resetTable();
                }),
        ];
    }
}
