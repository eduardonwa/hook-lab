<?php

namespace App\Filament\Widgets;

use App\Models\Hook;
use Filament\Widgets\Widget;

class QuickHookGenerator extends Widget
{
    protected string $view = 'filament.widgets.quick-hook-generator';

    public ?int $selectedHookId = null;

    public array $recentHookIds = [];

    public function generateHook(): void
    {
        $query = Hook::query();

        if (! empty($this->recentHookIds)) {
            $query->whereNotIn('id', $this->recentHookIds);
        }

        $hookId = $query
            ->inRandomOrder()
            ->value('id');

        // Si ya se acabaron las opciones disponibles, reinicia el historial.
        if (! $hookId) {
            $this->recentHookIds = [];

            $hookId = Hook::query()
                ->inRandomOrder()
                ->value('id');
        }

        $this->selectedHookId = $hookId;

        $this->recentHookIds[] = $hookId;

        // Mantener solo los últimos 5.
        $this->recentHookIds = array_slice($this->recentHookIds, -7);
    }

    public function getSelectedHookProperty(): ?Hook
    {
        if (! $this->selectedHookId) {
            return null;
        }

        return Hook::query()->find($this->selectedHookId);
    }
}