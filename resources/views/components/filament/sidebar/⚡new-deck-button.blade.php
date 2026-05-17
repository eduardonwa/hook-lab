<?php

use Livewire\Component;

new class extends Component
{
    //
};

?>

<div x-data>
    <button
        type="button"
        wire:click="$dispatch('open-new-deck-modal')"
        x-bind:class="$store.sidebar.isOpen
            ? 'w-full justify-center px-2 py-1'
            : 'w-10 h-10 justify-center px-0 py-0 mx-auto'"
        class="flex items-center gap-1 bg-primary-600 hover:bg-primary-700 rounded-lg text-sm font-semibold text-white transition"
    >
        <x-heroicon-o-plus
            x-bind:class="$store.sidebar.isOpen ? 'h-4 w-4' : 'h-5 w-5'"
        />

        <span x-show="$store.sidebar.isOpen" x-cloak>
            Nueva baraja
        </span>
    </button>
</div>