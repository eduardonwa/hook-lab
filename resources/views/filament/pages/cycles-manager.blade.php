<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-end">
            {{ $this->createCycleAction }}
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->cycles as $cycle)
                <button
                    type="button"
                    wire:click="mountAction('viewCycle', { cycle_id: {{ $cycle->id }} })"
                    class="relative h-40 rounded-xl border-gray-200 dark:border-gray-800 bg-white p-5 text-left shadow-sm transition hover:shadow-md dark:bg-gray-900"
                >
                    <div class="flex h-full flex-col justify-between">
                        <div>
                            <h3 class="mt-1 text-lg font-bold">
                                {{ $cycle->name }}
                            </h3>
                        </div>

                        <p class="text-sm text-gray-500">
                            {{ $cycle->items_count }} cartas · {{ $cycle->bag_hooks_count }} en bolsa
                        </p>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>