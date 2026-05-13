<?php

namespace App\Filament\Widgets;

use App\Models\QuickTriggerState;
use App\Models\Trigger;
use App\Services\PlanLimitService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuickTriggerGenerator extends Widget
{
    protected string $view = 'filament.widgets.quick-trigger-generator';

    public ?int $selectedTriggerId = null;

    public array $recentTriggerIds = [];

    public function mount(): void
    {
        $this->loadLastQuickTriggerState();
    }

    public function generateTrigger(): void
    {
        $user = Auth::user();

        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        if (! $limits->canUseQuickTriggerGenerator($user)) {
            $this->loadLastQuickTriggerState();

            Notification::make()
                ->title('Límite diario alcanzado')
                ->body('El plan gratis permite 7 generaciones por día.')
                ->warning()
                ->send();

            return;
        }

        $query = $this->availableTriggersQuery();

        if (! empty($this->recentTriggerIds)) {
            $query->whereNotIn('id', $this->recentTriggerIds);
        }

        $triggerId = $query
            ->inRandomOrder()
            ->value('id');

        if (! $triggerId) {
            $this->recentTriggerIds = [];

            $triggerId = $this->availableTriggersQuery()
                ->inRandomOrder()
                ->value('id');
        }

        if (! $triggerId) {
            Notification::make()
                ->title('No hay triggers disponibles')
                ->body('No se encontró ningún trigger para generar.')
                ->warning()
                ->send();

            return;
        }

        $this->selectedTriggerId = $triggerId;

        $this->storeLastQuickTriggerState($triggerId);

        $this->recentTriggerIds[] = $triggerId;

        $this->recentTriggerIds = array_slice($this->recentTriggerIds, -7);

        $limits->recordQuickTriggerGeneratorUse($user);
    }

    protected function availableTriggersQuery(): Builder
    {
        $user = Auth::user();

        return Trigger::query()
            ->where('is_active', true)
            ->whereIn(
                'access_level',
                $user->isPro()
                    ? ['free', 'pro']
                    : ['free']
            );
    }

    public function getSelectedTriggerProperty(): ?Trigger
    {
        if (! $this->selectedTriggerId) {
            return null;
        }

        return Trigger::query()->find($this->selectedTriggerId);
    }

    protected function storeLastQuickTriggerState(int $triggerId): void
    {
        $user = Auth::user();

        if ($user->isPro()) {
            return;
        }

        QuickTriggerState::updateOrCreate(
            ['user_id' => $user->id],
            [
                'trigger_id' => $triggerId,
                'expires_at' => now()->addDay(),
            ]
        );
    }

    protected function loadLastQuickTriggerState(): void
    {
        $user = Auth::user();

        if ($user->isPro()) {
            return;
        }

        $state = $user
            ->quickTriggerState()
            ->where('expires_at', '>', now())
            ->first();

        if (! $state) {
            return;
        }

        $this->selectedTriggerId = $state->trigger_id;
    }

    public function getQuickTriggerUsageLabelProperty(): ?string
    {
        $user = Auth::user();

        if ($user->isPro()) {
            return null;
        }

        $limit = $user->limits()['max_daily_quick_triggers'] ?? null;

        if ($limit === null) {
            return null;
        }

        $remaining = app(PlanLimitService::class)
            ->quickTriggerGeneratorUsesRemaining($user);

        return "Te quedan {$remaining} de {$limit} giros hoy";
    }
}