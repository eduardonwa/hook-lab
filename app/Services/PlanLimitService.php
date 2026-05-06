<?php

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

        return $user->cycle()->count() < $limit;
    }

    public function canCreateIdeaInDeck(User $user, $deck): bool
    {
        $limit = $this->limit($user, 'max_ideas_per_deck');

        if (is_null($limit)) {
            return true;
        }

        return $deck->items()->count() < $limit;
    }

    public function canCreateGroup(User $user): bool
    {
        $limit = $this->limit($user, 'max_groups');

        if (is_null($limit)) {
            return true;
        }

        return $user->groups()->count() < $limit;
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

    public function canCreateCustomHook(User $user): bool
    {
        $limit = $this->limit($user, 'max_custom_hooks');

        if (is_null($limit)) {
            return true;
        }

        return $user->customHooks()->count() < $limit;
    }
}