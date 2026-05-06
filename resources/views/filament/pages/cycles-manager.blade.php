<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-end">
            {{ $this->createCycleAction }}
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->cycles as $cycle)
                <div class="group relative pt-7">
                    {{-- Quick actions outside deck --}}
                    <div class="absolute right-2 top-0 flex gap-1 opacity-70 transition group-hover:opacity-100">
                        {{-- Go to board --}}
                        <x-filament::icon-button
                            tag="a"
                            href="{{ \App\Filament\Pages\CycleBoard::getUrl(['cycle' => $cycle->id]) }}"
                            icon="heroicon-o-pencil-square"
                            color="gray"
                            size="sm"
                            tooltip="Editar cartas"
                        />

                        {{-- Delete deck --}}
                        <x-filament::icon-button
                            icon="heroicon-o-trash"
                            color="danger"
                            size="sm"
                            tooltip="Eliminar baraja"
                            wire:click="mountAction('removeCycle', { cycle_id: {{ $cycle->id }} })"
                        />
                    </div>

                    {{-- Deck button opens preview --}}
                    <button
                        type="button"
                        wire:click="mountAction('viewCycle', { cycle_id: {{ $cycle->id }} })"
                        class="relative h-40 w-full rounded-xl border border-gray-200 bg-white p-5 text-left shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
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
                </div>
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>