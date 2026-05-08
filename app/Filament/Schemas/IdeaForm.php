<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class IdeaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Select::make('hook_id')
                ->relationship(
                    name: 'hook',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query): Builder {
                        $user = Auth::user();

                        return $query
                            ->where(function (Builder $query) use ($user) {
                                $query
                                    ->where('user_id', $user->id)
                                    ->orWhere(function (Builder $query) use ($user) {
                                        $query
                                            ->whereNull('user_id')
                                            ->whereIn(
                                                'access_level',
                                                $user->isPro()
                                                    ? ['free', 'pro']
                                                    : ['free']
                                            );
                                    });
                            })
                            ->orderBy('name');
                    }
                )
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('title')
                ->label('Idea')
                ->required(),
            Textarea::make('description')
                ->label('Descripción')
                ->columnSpanFull(),
        ];
    }
}