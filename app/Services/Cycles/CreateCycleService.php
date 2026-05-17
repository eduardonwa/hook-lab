<?php

namespace App\Services\Cycles;

use App\Models\Trigger;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\DB;

class CreateCycleService
{
    public function create(User $user, array $data): void
    {
        /** @var PlanLimitService $limits */
        $limits = app(PlanLimitService::class);

        if (! $limits->canCreateDeck($user)) {
            throw new \RuntimeException('deck_limit_reached');
        }

        DB::transaction(function () use ($data, $user, $limits) {
            $user->cycles()->update([
                'is_active' => false,
            ]);

            $selectedTriggerIds = [];

            if ($data['start_mode'] === 'random_triggers') {
                $totalTriggers = $this->availableTriggersQuery($user)->count();

                $count = min(
                    (int) ($data['random_triggers_count'] ?? 1),
                    $totalTriggers,
                );

                $selectedTriggerIds = $this->availableTriggersQuery($user)
                    ->inRandomOrder()
                    ->limit($count)
                    ->pluck('id')
                    ->all();
            }

            if ($data['start_mode'] === 'group_triggers') {
                $selectedTriggerIds = $this->availableTriggersQuery($user)
                    ->join('trigger_trigger_group', 'triggers.id', '=', 'trigger_trigger_group.trigger_id')
                    ->whereIn('trigger_trigger_group.trigger_group_id', $data['trigger_group_ids'] ?? [])
                    ->orderBy('trigger_trigger_group.sort_order')
                    ->orderBy('triggers.name')
                    ->select('triggers.id')
                    ->pluck('triggers.id')
                    ->all();
            }

            $selectedTriggerIds = collect($selectedTriggerIds)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $maxCombosPerDeck = $limits->limit($user, 'max_combos_per_deck');

            if (! is_null($maxCombosPerDeck)) {
                $selectedTriggerIds = collect($selectedTriggerIds)
                    ->take($maxCombosPerDeck)
                    ->values()
                    ->all();
            }

            $cycle = $user->cycles()->create([
                'name' => $data['name'],
                'generation_mode' => match ($data['start_mode']) {
                    'random_triggers' => 'random',
                    'group_triggers' => 'group',
                    default => 'random',
                },
                'size' => count($selectedTriggerIds),
                'is_active' => true,
            ]);

            if (! empty($selectedTriggerIds)) {
                $triggers = Trigger::query()
                    ->whereIn('id', $selectedTriggerIds)
                    ->get()
                    ->sortBy(fn ($trigger) => array_search($trigger->id, $selectedTriggerIds))
                    ->values();

                foreach ($triggers as $index => $trigger) {
                    $cycle->items()->create([
                        'trigger_id' => $trigger->id,
                        'hook_id' => null,
                        'hook_text' => null,
                        'idea_text' => null,
                        'position' => $index + 1,
                    ]);
                }
            }

            $remainingTriggerIds = $this->availableTriggersQuery($user)
                ->whereNotIn('id', $selectedTriggerIds)
                ->pluck('id')
                ->all();

            if (! empty($remainingTriggerIds)) {
                $now = now();

                DB::table('cycle_trigger_bag')->insert(
                    collect($remainingTriggerIds)
                        ->map(fn ($triggerId) => [
                            'cycle_id' => $cycle->id,
                            'trigger_id' => $triggerId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );
            }
        });
    }

    protected function availableTriggersQuery(User $user)
    {
        return Trigger::query()
            ->where('is_active', true)
            ->whereIn(
                'access_level',
                $user->isPro()
                    ? ['free', 'pro']
                    : ['free']
            );
    }
}