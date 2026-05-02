<?php

namespace App\Filament\Widgets;

use App\Models\Hook;
use Filament\Widgets\Widget;

class QuickHookGenerator extends Widget
{
    protected string $view = 'filament.widgets.quick-hook-generator';

    public ?int $selectedHookId = null;

    public function generateHook(): void
    {
        $this->selectedHookId = Hook::query()
            ->inRandomOrder()
            ->value('id');
    }

    public function getSelectedHookProperty(): ?Hook
    {
        if (! $this->selectedHookId) {
            return null;
        }

        return Hook::query()->find($this->selectedHookId);
    }
}
