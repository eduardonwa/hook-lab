<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class EmojiSlider extends Field
{
    protected string $view = 'filament.forms.components.emoji-slider';

    protected array $emojiLabels = [
        1 => '😵 Sin ganas',
        2 => '😐 Baja',
        3 => '🙂 Normal',
        4 => '🔥 Buena',
        5 => '⚡ Hagamos esto',
    ];

    protected bool $showOptionText = true;

    public function emojiLabels(array $labels): static
    {
        $this->emojiLabels = $labels;

        return $this;
    }

    public function showOptionText(bool $condition = true): static
    {
        $this->showOptionText = $condition;

        return $this;
    }

    public function shouldShowOptionText(): bool
    {
        return $this->showOptionText;
    }

    public function getEmojiLabels(): array
    {
        return $this->emojiLabels;
    }
}
