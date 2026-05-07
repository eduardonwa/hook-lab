<?php

namespace App\Models;

use App\Models\Cycle;
use App\Models\Hook;
use App\Models\Idea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class CycleItem extends Model
{
    public const BOARD_STATE_DECK = 'deck';
    public const BOARD_STATE_TABLE = 'table';
    
    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime'
    ];
    
    protected static function booted(): void
    {
        static::saving(function ($item) {
            if ($item->idea_id) {
                $idea = Idea::find($item->idea_id);

                if ($idea && $idea->hook_id !== $item->hook_id) {
                    throw ValidationException::withMessages([
                        'idea_id' => 'The selected idea does not belong to the same hook as this cycle item.'
                    ]);
                }
            }
        });
    }
    
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function hook(): BelongsTo
    {
        return $this->belongsTo(Hook::class);
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function isInDeck(): bool
    {
        return $this->board_state === self::BOARD_STATE_DECK;
    }

    public function isOnTable(): bool
    {
        return $this->board_state === self::BOARD_STATE_TABLE;
    }
}
