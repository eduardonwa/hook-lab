<?php

namespace App\Models;

use App\Models\Trigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickTriggerState extends Model
{
    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
