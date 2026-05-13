<?php

namespace App\Models;

use App\Models\CycleItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CycleItem::class)
            ->orderBy('position');
    }

    public function bagTriggers(): BelongsToMany
    {
        return $this->belongsToMany(
            Trigger::class,
            'cycle_trigger_bag',
            'cycle_id',
            'trigger_id'
        )->withTimestamps();
    }
}
