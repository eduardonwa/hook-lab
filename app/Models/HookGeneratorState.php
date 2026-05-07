<?php

namespace App\Models;

use App\Models\Hook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HookGeneratorState extends Model
{
    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function hook(): BelongsTo
    {
        return $this->belongsTo(Hook::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
