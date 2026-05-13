<?php

namespace App\Models;

use App\Models\Cycle;
use App\Models\Trigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleTriggerBag extends Model
{
    protected $table = 'cycle_trigger_bag';
    
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }
}
