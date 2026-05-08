<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\HookForm;
use App\Models\Hook;
use App\Models\HookGroup;
use App\Services\PlanLimitService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    protected function planLimits(): PlanLimitService
    {
        return app(PlanLimitService::class);
    }

    protected function availableHooksQuery(): Builder
    {
        $user = Auth::user();

        return Hook::query()
            ->where(function (Builder $query) use ($user) {
                // Hooks personalizados del usuario
                $query->where('user_id', $user->id)

                    // Hooks base según plan
                    ->orWhere(function (Builder $query) use ($user) {
                        $query->whereNull('user_id')
                            ->where('access_level', $user->isPro() ? 'pro' : 'free');
                    });
            });
    }

    protected function userHookGroupsQuery(): Builder
    {
        return HookGroup::query()
            ->where('user_id', Auth::id());
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->expandedHookId = null;
    }

    public function mount(): void
    {
        $this->selectedGroupId = $this->userHookGroupsQuery()
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

        unset($this->hookGroups);
    }
    
    public function getHookGroupsProperty(): Collection
    {
        return $this->userHookGroupsQuery()
            ->withCount('hooks')
            ->orderBy('created_at', $this->groupSortDirection)
            ->get();
    }

    public function getSelectedGroupProperty(): ?HookGroup
    {
        if (! $this->selectedGroupId) {
            return null;
        }

        return $this->userHookGroupsQuery()
            ->with([
                'hooks' => fn ($query) => $query->orderByPivot('sort_order'),
            ])
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
                    return 'gray';
                }

                return $group->hooks()->exists()
                    ? 'gray'
                    : 'gray';
            })
            ->icon('heroicon-o-pencil')
            ->link()
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
                        return $this->availableHooksQuery()
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

                $group = $this->userHookGroupsQuery()->find($this->selectedGroupId);

                if (! $group) {
                    return;
                }

                HookGroup::query()
                    ->whereKey($group->id)
                    ->update([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                    ]);

                $hookIds = $this->availableHooksQuery()
                    ->whereIn('id', $data['hook_ids'] ?? [])
                    ->pluck('id')
                    ->toArray();

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

        $group = $this->userHookGroupsQuery()->find($this->selectedGroupId);

        if (! $group) {
            return;
        }

        $allowedHookIds = $this->availableHooksQuery()
            ->whereIn('id', $orderedHookIds)
            ->pluck('id')
            ->toArray();

        foreach ($orderedHookIds as $index => $hookId) {
            if (! in_array($hookId, $allowedHookIds)) {
                continue;
            }

            DB::table('hook_hook_group')
                ->where('hook_group_id', $group->id)
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
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Borrar grupo de hooks')
            ->modalDescription('Esto solo borrará el grupo. Tus hooks seguirán existiendo.')
            ->modalSubmitActionLabel('Si, borrar grupo')
            ->disabled(fn () => ! $this->selectedGroupId)
            ->action(function (): void {
                if (! $this->selectedGroupId) {
                    return;
                }

                $group = $this->userHookGroupsQuery()->find($this->selectedGroupId);

                if (! $group) {
                    return;
                }

                HookGroup::query()
                    ->whereKey($group->id)
                    ->delete();

                $this->selectedGroupId = $this->userHookGroupsQuery()
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
            ->query($this->availableHooksQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('access_level')
                    ->label('Plan')
                    ->state(function (Hook $record): string {
                        if ($record->user_id) {
                            return '—';
                        }

                        return match ($record->access_level) {
                            'free' => 'Free',
                            'pro' => 'Pro',
                            default => ucfirst($record->access_level),
                        };
                    })
                    ->badge()
                    ->color(fn (Hook $record): string => match ($record->access_level) {
                        'free' => 'success',
                        'pro' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->state(fn (Hook $record): string => $record->user_id ? 'Creado' : 'Lab')
                    ->badge()
                    ->color(fn (Hook $record): string => $record->user_id ? 'gray' : 'info'),
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
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Ver hook')
                    ->modalWidth(Width::Medium)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->color('gray')
                                    ->label('Nombre'),
                                TextEntry::make('description')
                                    ->label('Descripción')
                                    ->color('gray')
                                    ->columnSpanFull()
                            ]),
                    ]),
                EditAction::make()
                    ->modalHeading('Editar hook')
                    ->schema(HookForm::getFormSchema())
                    ->visible(fn (Hook $record): bool => $record->user_id === Auth::id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => true)
                        ->action(function ($records): void {
                            $records
                                ->where('user_id', Auth::id())
                                ->each
                                ->delete();
                        }),
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
                ->before(function (CreateAction $action): void {
                    $user = Auth::user();

                    if (! $this->planLimits()->canCreateGroup($user)) {
                        Notification::make()
                            ->title('Llegaste al límite de grupos')
                            ->body('Tu plan Free te permite crear hasta 2 grupos de hooks')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                })
                ->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = Auth::id();

                    return $data;
                })
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
                ->before(function (CreateAction $action): void {
                    $user = Auth::user();

                    if (! $this->planLimits()->canCreateCustomHook($user)) {
                        Notification::make()
                            ->title('Llegaste al límite de hooks personalizados')
                            ->body('Tu plan Free permite crear hasta 10 hooks personalizados')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                })
                ->mutateDataUsing(function (array $data): array {
                    $baseSlug = Str::slug($data['name']);

                    $slug = $baseSlug;
                    $counter = 2;

                    while (Hook::where('slug', $slug)->exists()) {
                        $slug = "{$baseSlug}-{$counter}";
                        $counter++;
                    }

                    $data['user_id'] = Auth::id();
                    $data['access_level'] = 'custom';
                    $data['slug'] = $slug;
                    
                    return $data;
                })
                ->after(function (): void {
                    $this->resetTable();
                }),
        ];
    }
}
