<?php

namespace App\Models;

use App\Models\CycleItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CycleItem::class)
            ->orderBy('position');
    }
}
