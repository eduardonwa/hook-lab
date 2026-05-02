<?php

namespace App\Models;

use App\Models\Hook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HookGroup extends Model
{
    public function hooks(): BelongsToMany
    {
        return $this->belongsToMany(Hook::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
