<?php

namespace App\Models;

use App\Models\Cycle;
use App\Models\CycleItem;
use App\Models\Hook;
use App\Models\TriggerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trigger extends Model
{
    public function hooks(): HasMany
    {
        return $this->hasMany(Hook::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TriggerGroup::class)
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
            'cycle_trigger_bag',
            'trigger_id',
            'cycle_id'
        )->withTimestamps();
    }
}
