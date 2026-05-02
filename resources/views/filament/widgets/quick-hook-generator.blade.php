<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Ruleta de hooks
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Saca un hook del baúl
                    </p>
                </div>

                <x-filament::button
                    wire:click="generateHook"
                    wire:loading.attr="disabled"
                    wire:target="generateHook"
                    icon="heroicon-m-sparkles"
                >
                    <span wire:loading.remove wire:target="generateHook">
                        Girar hook
                    </span>

                    <span wire:loading wire:target="generateHook">
                        Girando...
                    </span>
                </x-filament::button>
            </div>

            <div
                wire:loading.class="animate-pulse opacity-60"
                wire:target="generateHook"
                class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition duration-300 dark:border-white/10 dark:bg-white/5"
            >
                @if ($this->selectedHook)
                    <p class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $this->selectedHook->name }}
                    </p>
                    {{-- dejar en una sola linea por la identación del <p> --}}
                    <p class="whitespace-pre-line mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ trim($this->selectedHook->description) }}</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Gira primero. Deja que el sistema elija tu suerte.
                    </p>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>