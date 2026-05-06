<?php

namespace App\Filament\Widgets;

use App\Models\Hook;
use App\Services\PlanLimitService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickHookGenerator extends Widget
{
    protected string $view = 'filament.widgets.quick-hook-generator';

    public ?int $selectedHookId = null;

    public array $recentHookIds = [];

    public function generateHook(): void
    {
        $user = Auth::user();

        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        if (! $limits->canUseQuickHookGenerator($user)) {
            Notification::make()
                ->title('Límite diario alcanzado')
                ->body('El plan gratis permite 7 generaciones por día.')
                ->warning()
                ->send();

            return;
        }

        $query = Hook::query();

        if (! $user->isPro()) {
            $query->where('access_level', 'free');
        }

        if (! empty($this->recentHookIds)) {
            $query->whereNotIn('id', $this->recentHookIds);
        }

        $hookId = $query
            ->inRandomOrder()
            ->value('id');

        if (! $hookId) {
            $this->recentHookIds = [];

            $query = Hook::query();

            if (! $user->isPro()) {
                $query->where('access_level', 'free');
            }

            $hookId = $query
                ->inRandomOrder()
                ->value('id');
        }

        if (! $hookId) {
            Notification::make()
                ->title('No hay hooks disponibles')
                ->body('No se encontró ningún hook para generar.')
                ->warning()
                ->send();

            return;
        }

        $this->selectedHookId = $hookId;

        $this->recentHookIds[] = $hookId;

        $this->recentHookIds = array_slice($this->recentHookIds, -7);

        $limits->recordQuickHookGeneratorUse($user);
    }

    public function getSelectedHookProperty(): ?Hook
    {
        if (! $this->selectedHookId) {
            return null;
        }

        return Hook::query()->find($this->selectedHookId);
    }
}