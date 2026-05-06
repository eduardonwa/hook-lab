<?php

namespace App\Models;

use App\Models\Cycle;
use App\Models\Hook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleHookBag extends Model
{
    protected $table = 'cycle_hook_bag';
    
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function hook(): BelongsTo
    {
        return $this->belongsTo(Hook::class);
    }
}
