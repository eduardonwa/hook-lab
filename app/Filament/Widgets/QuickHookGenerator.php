<?php

namespace App\Filament\Widgets;

use App\Models\Hook;
use App\Models\HookGeneratorState;
use App\Services\PlanLimitService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickHookGenerator extends Widget
{
    protected string $view = 'filament.widgets.quick-hook-generator';

    public ?int $selectedHookId = null;

    public array $recentHookIds = [];

    public function mount(): void
    {
        $this->loadLastHookGeneratorState();
    }

    public function generateHook(): void
    {
        $user = Auth::user();

        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        if (! $limits->canUseQuickHookGenerator($user)) {
            $this->loadLastHookGeneratorState();

            Notification::make()
                ->title('Límite diario alcanzado')
                ->body('El plan gratis permite 7 generaciones por día.')
                ->warning()
                ->send();

            return;
        }

        $query = Hook::query()
            ->whereNull('user_id')
            ->where('access_level', $user->isPro() ? 'pro' : 'free');

        if (! empty($this->recentHookIds)) {
            $query->whereNotIn('id', $this->recentHookIds);
        }

        $hookId = $query
            ->inRandomOrder()
            ->value('id');

        if (! $hookId) {
            $this->recentHookIds = [];

            $query = Hook::query()
                ->whereNull('user_id')
                ->where('access_level', $user->isPro() ? 'pro' : 'free');

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

        $this->storeLastHookGeneratorState($hookId);

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

    protected function storeLastHookGeneratorState(int $hookId): void
    {
        $user = Auth::user();

        if ($user->isPro()) {
            return;
        }

        HookGeneratorState::updateOrCreate(
            ['user_id' => $user->id],
            [
                'hook_id' => $hookId,
                'expires_at' => now()->addDay(),
            ]
        );
    }

    protected function loadLastHookGeneratorState(): void
    {
        $user = Auth::user();

        if ($user->isPro()) {
            return;
        }

        $state = $user
            ->hookGeneratorState()
            ->where('expires_at', '>', now())
            ->first();

        if (! $state) {
            return;
        }

        $this->selectedHookId = $state->hook_id;
    }

    public function getQuickHookUsageLabelProperty(): ?string
    {
        $user = Auth::user();

        if ($user->isPro()) {
            return null;
        }

        $limit = $user->limits()['max_daily_quick_hooks'] ?? null;

        if ($limit === null) {
            return null;
        }

        $remaining = app(PlanLimitService::class)
            ->quickHookGeneratorUsesRemaining($user);

        return "Te quedan {$remaining} de {$limit} giros hoy";
    }
}