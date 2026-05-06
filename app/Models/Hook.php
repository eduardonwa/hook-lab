<?php

namespace App\Models;

use App\Models\CycleItem;
use App\Models\HookGroup;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hook extends Model
{
    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(HookGroup::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function cycleItems(): HasMany
    {
        return $this->hasMany(CycleItem::class);
    }

    public function cycleBags(): BelongsToMany
    {
        return $this->belongsToMany(
            Cycle::class,
            'cycle_hook_bag',
            'hook_id',
            'cycle_id'
        )->withTimestamps();
    }
}
