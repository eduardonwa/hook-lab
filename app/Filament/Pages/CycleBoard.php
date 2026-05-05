<?php

namespace App\Filament\Pages;

use App\Filament\Pages\CyclesManager;
use App\Models\Cycle;
use App\Models\CycleItem;
use App\Models\Idea;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class CycleBoard extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.pages.cycle-board';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'barajas/{cycle}/board';
    protected static ?string $title = '';
    public string $viewMode = 'cards';

    public Cycle $cycle;
    public ?int $editingItemId = null;
    public ?string $editingHookName = null;
    public ?string $editingHookDescription = null;

    public function mount(Cycle $cycle): void
    {
        $this->cycle = $cycle;
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

                $this->dispatch('$refresh');
            });
    }

    public function editIdeaAction(): Action
    {
        return Action::make('editIdea')
            ->label('Editar idea')
            ->icon('heroicon-o-pencil-square')
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

                $this->dispatch('$refresh');
            })
            ->slideOver();
    }
}
