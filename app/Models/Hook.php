<?php

namespace App\Models;

use App\Models\CycleItem;
use App\Models\Trigger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hook extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public function cycleItems(): HasMany
    {
        return $this->hasMany(CycleItem::class);
    }
}
