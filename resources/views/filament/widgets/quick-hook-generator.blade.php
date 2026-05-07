<x-filament-widgets::widget>
    <div>
        <div class="flex flex-col gap-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Saca hook
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Gira y descubre qué publicar hoy
                    </p>
                </div>

                <x-filament::button
                    wire:click="generateHook"
                    wire:loading.attr="disabled"
                    wire:target="generateHook"
                    icon="heroicon-m-arrow-path"
                >
                    <span wire:loading.remove wire:target="generateHook">
                        Girar
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
                        Deja que caiga
                    </p>
                @endif
            </div>

                @if ($this->quickHookUsageLabel)
                    <p class="text-xs text-gray-400 text-center">
                        {{ $this->quickHookUsageLabel }}
                    </p>
                @endif

            @if (! auth()->user()->isPro() && ! app(\App\Services\PlanLimitService::class)->canUseQuickHookGenerator(auth()->user()))
                <p class="text-center text-sm text-info-400">
                    Obtén giros ilimitados con el plan Pro
                </p>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>