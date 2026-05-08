<?php

namespace App\Models;

use App\Models\CycleItem;
use App\Models\Hook;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idea extends Model
{
    public function hook(): BelongsTo
    {
        return $this->belongsTo(Hook::class);
    }

    public function cycleItems(): HasMany
    {
        return $this->hasMany(CycleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
