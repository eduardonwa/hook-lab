<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\IdeaForm;
use App\Models\Idea;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

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
        return [
            CreateAction::make('createIdea')
                ->label('Nueva idea')
                ->icon('heroicon-o-plus')
                ->model(Idea::class)
                ->schema(IdeaForm::getFormSchema())
                ->after(function (): void {
                    $this->resetTable();
                }),
        ];
    } 

    public function table(Table $table): Table
    {
        return $table
            ->query(Idea::query())
            ->columns([
                TextColumn::make('hook.name')
                    ->label('Hook')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Título')
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
                    ->schema(IdeaForm::getFormSchema())
            ])
            ->toolbarActions([
                
            ]);
    }
}
