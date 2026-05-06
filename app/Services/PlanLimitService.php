<?php

namespace App\Services;

use App\Models\UsageEvent;
use App\Models\User;

class PlanLimitService
{
    public function limit(User $user, string $key): mixed
    {
        return $user->limits()[$key] ?? null;
    }

    public function unlimited(User $user, string $key): bool
    {
        return is_null($this->limit($user, $key));
    }

    public function canCreateDeck(User $user): bool
    {
        $limit = $this->limit($user, 'max_decks');

        if (is_null($limit)) {
            return true;
        }

        return $user->cycles()->count() < $limit;
    }

    public function canCreateCycleItemInDeck(User $user, $cycle): bool
    {
        $limit = $this->limit($user, 'max_ideas_per_deck');

        if (is_null($limit)) {
            return true;
        }

        return $cycle->items()->count() < $limit;
    }

    public function canCreateGroup(User $user): bool
    {
        $limit = $this->limit($user, 'max_groups');

        if (is_null($limit)) {
            return true;
        }

        return $user->groups()->count() < $limit;
    }

    public function canCreateCustomHook(User $user): bool
    {
        $limit = $this->limit($user, 'max_custom_hooks');

        if (is_null($limit)) {
            return true;
        }

        return $user->customHooks()->count() < $limit;
    }

    public function canPinMoreItems(User $user): bool
    {
        $limit = $this->limit($user, 'max_pinned_items');

        if (is_null($limit)) {
            return true;
        }

        return $user->items()
            ->where('is_pinned', true)
            ->count() < $limit;
    }

    
    public function canUseQuickHookGenerator(User $user): bool
    {
        $limit = $this->limit($user, 'max_daily_quick_hooks');

        if (is_null($limit)) {
            return true;
        }

        $usedToday = UsageEvent::query()
            ->where('user_id', $user->id)
            ->where('feature', 'quick_hook_generator')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $usedToday < $limit;
    }

    public function recordQuickHookGeneratorUse(User $user): void
    {
        UsageEvent::create([
            'user_id' => $user->id,
            'feature' => 'quick_hook_generator',
        ]);
    }

    public function quickHookGeneratorUsesRemaining(User $user): ?int
    {
        $limit = $this->limit($user, 'max_daily_quick_hooks');

        if (is_null($limit)) {
            return null;
        }

        $usedToday = UsageEvent::query()
            ->where('user_id', $user->id)
            ->where('feature', 'quick_hook_generator')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return max(0, $limit - $usedToday);
    }
}