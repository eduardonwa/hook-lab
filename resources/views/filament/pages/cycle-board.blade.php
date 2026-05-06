<x-filament-panels::page>
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Baraja
                </p>

                <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                    {{ $this->cycle->name }}
                </h1>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->itemsCount }} cartas · {{ $this->bagHooksCount }} hooks en bolsa
                </p>
            </div>

            <div class="flex gap-2">
                <x-filament::button
                    size="sm"
                    color="{{ $viewMode === 'cards' ? 'primary' : 'gray' }}"
                    wire:click="$set('viewMode', 'cards')"
                >
                    Cartas
                </x-filament::button>

                <x-filament::button
                    size="sm"
                    color="{{ $viewMode === 'table' ? 'primary' : 'gray' }}"
                    wire:click="$set('viewMode', 'table')"
                >
                    Tabla
                </x-filament::button>
            </div>

            @if ($this->bagHooksCount > 0)
                <x-filament::button
                    size="sm"
                    color="gray"
                    wire:click="mountAction('addFromBag')"
                >
                    Agregar desde bolsa
                </x-filament::button>
            @else
                <span class="text-xs text-gray-500">
                    Bolsa vacía
                </span>
            @endif
        </section>

        @if ($viewMode === 'cards')
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->items as $item)
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Carta #{{ $item->position }}
                                    </p>

                                    <h3 class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                        {{ $item->hook?->name ?? '-' }}
                                    </h3>
                                </div>
                            </div>

                            <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">
                                {{ $item->hook?->description ?? 'Sin descripción.' }}
                            </p>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-gray-950">
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Idea
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item->idea?->title ?? 'Sin idea asignada' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-1">
                                {{ ($this->editItemAction)(['item_id' => $item->id]) }}

                                @if ($item->idea_id)
                                    {{ ($this->editIdeaAction)(['item_id' => $item->id]) }}
                                @endif

                                {{ ($this->removeItemAction)(['item_id' => $item->id]) }}
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-10 text-center dark:border-white/10">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Esta baraja todavía no tiene cartas.
                        </p>
                    </div>
                @endforelse
            </section>
        @endif

        @if ($viewMode === 'table')
            <section class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <div class="max-h-[60vh] overflow-y-auto">
                    <div class="overflow-x-auto">
                        <table class="min-w-[520px] w-full table-fixed text-sm">
                            <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-950">
                                <tr class="border-b border-gray-200 dark:border-white/10">
                                    <th class="w-16 p-3 text-left font-semibold text-gray-950 dark:text-white">
                                        #
                                    </th>

                                    <th class="w-56 p-3 text-left font-semibold text-gray-950 dark:text-white">
                                        Hook
                                    </th>

                                    <th class="w-56 p-3 text-left font-semibold text-gray-950 dark:text-white">
                                        Idea
                                    </th>

                                    <th class="w-40 p-3 text-right font-semibold text-gray-950 dark:text-white">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($this->items as $item)
                                    <tr class="border-t border-gray-200 dark:border-white/10">
                                        <td class="w-16 p-3 text-gray-500 dark:text-gray-400">
                                            {{ $item->position }}
                                        </td>

                                        <td class="w-56 truncate p-3 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $item->hook?->name ?? '-' }}
                                        </td>

                                        <td class="w-56 truncate p-3 text-gray-500 dark:text-gray-400">
                                            {{ $item->idea?->title ?? '-' }}
                                        </td>

                                        <td class="w-40 p-3">
                                            <div class="flex justify-end gap-2">
                                                {{ ($this->editItemAction)(['item_id' => $item->id]) }}

                                                @if ($item->idea_id)
                                                    {{ ($this->editIdeaAction)(['item_id' => $item->id]) }}
                                                @endif

                                                {{ ($this->removeItemAction)(['item_id' => $item->id]) }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                            Esta baraja todavía no tiene cartas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>