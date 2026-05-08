<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\IdeaForm;
use App\Models\Idea;
use App\Services\PlanLimitService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class IdeaManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.idea-manager';

    protected static string | \UnitEnum | null $navigationGroup = 'Planeador';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Ideas';
    protected static ?string $title = 'Ideas';

    public function getHeaderActions(): array
    {
        $user = Auth::user();
        
        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        if (! $limits->canCreateIdea($user)) {
            return [
                $this->subscribeAction(),
            ];
        }

        $ideasRemaining = $limits->ideasRemaining($user);

        return [
            CreateAction::make('createIdea')
                ->label('Nueva idea')
                ->icon('heroicon-o-plus')
                ->model(Idea::class)
                ->schema(IdeaForm::getFormSchema())
                ->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = Auth::id();

                    return $data;
                })
                ->after(function (): void {
                    $this->resetTable();
                }),
        ];
    }

    public function subscribeAction(): Action
    {
        return Action::make('subscribe')
            ->label('¿Necesitas más?')
            ->color('primary')
            ->modalHeading('Desbloquea más ideas')
            ->modalWidth(Width::Medium)
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalDescription(null)
            ->modalContent(new HtmlString('
                <div class="space-y-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>Tu plan Free permite crear 10 ideas. <br> Suscríbete a Pro para crear ideas ilimitadas.</p>
                </div>
            '))
            ->modalSubmitActionLabel('Ver planes')
            ->action(function (): void {
                if (! config('services.stripe.billing_enabled')) {
                    Notification::make()
                        ->title('Pro muy pronto')
                        ->body('El plan Pro aún no está activado. Sigue usando Hook Lab en modo Free.')
                        ->info()
                        ->send();

                    return;
                }

                // Conectar checkout después
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Idea::query()
                    ->where('user_id', Auth::id())
                    ->with('hook')
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Idea')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hook.name')
                    ->label('Hook')
                    ->searchable()
                    ->sortable(),

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
                    ->modalHeading('Editar Idea')
                    ->schema(IdeaForm::getFormSchema()),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                ]),
            ]);
    }
}
