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
            <section class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->items as $item)
                    @php
                        $isInDeck = ($item->board_state ?? 'deck') === 'deck';
                    @endphp

                    @if ($isInDeck)
                        {{-- CARD REVERSE --}}
                        <button
                            wire:key="cycle-card-reverse-{{ $item->id }}"
                            type="button"
                            wire:click="bringCardToTable({{ $item->id }})"
                            wire:transition.opacity.scale.duration.250ms
                            class="group relative mx-auto aspect-[147/204] w-full max-w-[300px] overflow-hidden rounded-2xl border border-gray-200 bg-gray-950 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 sm:max-w-none"
                        >
                            <img
                                src="{{ asset('images/card-2-reverse.svg') }}"
                                alt="Reverso de carta"
                                class="absolute inset-0 h-full w-full object-cover"
                            >

                            <div class="absolute inset-0 bg-black/10 transition group-hover:bg-black/0"></div>

                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="rounded-full border border-white/10 bg-black/45 px-4 py-2 backdrop-blur">
                                    <p class="text-sm font-semibold text-white">
                                        #{{ $item->position }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    @else
                        {{-- CARD FRONT --}}
                        <article
                            wire:key="cycle-card-front-{{ $item->id }}"
                            wire:transition.opacity.scale.duration.250ms
                            class="mx-auto flex aspect-[147/204] w-full max-w-[300px] flex-col gap-3 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:max-w-none"
                        >
                            {{-- INNER TOP BAR --}}
                            <div class="flex justify-end gap-1">
                                {{-- MAZO BTN --}}
                                <button
                                    type="button"
                                    wire:click="sendCardToDeck({{ $item->id }})"
                                    x-tooltip.raw="Ocultar carta"
                                    aria-label="Meter al mazo"
                                    class="inline-flex items-center rounded-lg p-1.5 text-xs font-medium text-gray-400 transition hover:text-primary-600"
                                >
                                    @svg('heroicon-o-eye-slash', 'h-4 w-4')
                                </button>

                                {{-- FIJAR BTN --}}
                                <button
                                    type="button"
                                    wire:click="togglePinItem({{ $item->id }})"
                                    x-tooltip.raw="{{ $item->is_pinned ? 'Desfijar' : 'Fijar' }}"
                                    aria-label="{{ $item->is_pinned ? 'Desfijar' : 'Fijar' }}"
                                    class="inline-flex items-center rounded-lg p-1.5 text-xs font-medium transition
                                        {{ $item->is_pinned
                                            ? 'bg-info-50 text-info-600 dark:bg-info-500/10'
                                            : 'text-gray-400 hover:text-info-600' }}"
                                >
                                    @if ($item->is_pinned)
                                        @svg('heroicon-s-bookmark', 'h-4 w-4')
                                    @else
                                        @svg('heroicon-o-bookmark', 'h-4 w-4')
                                    @endif
                                </button>
                            </div>
                            
                            {{-- HOOK DESCRIPTION --}}
                            <div>
                                <div class="text-base font-bold text-gray-950 dark:text-white flex items-center">
                                    <span class="mr-1 text-sm font-medium text-gray-400 dark:text-gray-500">
                                        #{{ $item->position }}
                                    </span>

                                    <span class="text-xl sm:text-xl lg:text-[1.3rem]">
                                        {{ $item->hook?->name ?? '-' }}
                                    </span>
                                </div>

                                <p class="whitespace-pre-line text-md leading-6 text-gray-500 dark:text-gray-400">
                                    {{ trim($item->hook?->description ?? 'Sin descripción.') }}
                                </p>
                            </div>

                            {{-- IDEA --}}
                            <div class="mt-auto rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-gray-950">
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Idea
                                </p>

                                <p class="mt-1 text-md font-medium text-gray-900 dark:text-gray-100">
                                    {{ Str::limit($item->idea?->title ?? 'Sin idea asignada', 24) }}
                                </p>
                            </div>

                            {{-- CARD ACTIONS --}}
                            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
                                {{-- Mobile --}}
                                <div class="flex gap-2 sm:hidden">
                                    @if ($item->idea_id)
                                        <x-filament::button
                                            color="gray"
                                            outlined
                                            size="sm"
                                            icon="heroicon-o-pencil-square"
                                            class="!px-2.5 !py-1.5 justify-center"
                                            wire:click="mountAction('editIdea', { item_id: {{ $item->id }} })"
                                        >
                                            Idea
                                        </x-filament::button>
                                    @endif

                                    <x-filament::button
                                        color="primary"
                                        outlined
                                        size="sm"
                                        icon="heroicon-o-adjustments-horizontal"
                                        class="!px-2.5 !py-1.5 justify-center"
                                        wire:click="mountAction('editItem', { item_id: {{ $item->id }} })"
                                    >
                                        Combo
                                    </x-filament::button>

                                    <x-filament::button
                                        color="danger"
                                        outlined
                                        size="sm"
                                        icon="heroicon-o-trash"
                                        class="!px-2.5 !py-1.5 justify-center"
                                        wire:click="mountAction('removeItem', { item_id: {{ $item->id }} })"
                                    >
                                        Quitar
                                    </x-filament::button>
                                </div>

                                {{-- Desktop --}}
                                <div class="hidden gap-2 sm:flex">
                                    @if ($item->idea_id)
                                        <x-filament::icon-button
                                            icon="heroicon-o-pencil-square"
                                            color="gray"
                                            size="sm"
                                            tooltip="Editar idea"
                                            wire:click="mountAction('editIdea', { item_id: {{ $item->id }} })"
                                        />
                                    @endif

                                    <x-filament::icon-button
                                        icon="heroicon-o-adjustments-horizontal"
                                        color="primary"
                                        size="sm"
                                        tooltip="Editar combo"
                                        wire:click="mountAction('editItem', { item_id: {{ $item->id }} })"
                                    />

                                    <x-filament::icon-button
                                        icon="heroicon-o-trash"
                                        color="danger"
                                        size="sm"
                                        tooltip="Quitar carta"
                                        wire:click="mountAction('removeItem', { item_id: {{ $item->id }} })"
                                    />
                                </div>
                            </div>
                        </article>
                    @endif
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

                                        {{-- CARD ACTIONS --}}
                                        <td class="w-40 p-3">
                                            <div class="flex justify-end gap-2">
                                                @if ($item->idea_id)
                                                    <x-filament::icon-button
                                                        icon="heroicon-o-pencil-square"
                                                        color="gray"
                                                        size="sm"
                                                        tooltip="Editar idea"
                                                        wire:click="mountAction('editIdea', { item_id: {{ $item->id }} })"
                                                    />
                                                @endif

                                                <x-filament::icon-button
                                                    icon="heroicon-o-adjustments-horizontal"
                                                    color="primary"
                                                    size="sm"
                                                    tooltip="Editar combo"
                                                    wire:click="mountAction('editItem', { item_id: {{ $item->id }} })"
                                                />

                                                <x-filament::icon-button
                                                    icon="heroicon-o-trash"
                                                    color="danger"
                                                    size="sm"
                                                    tooltip="Quitar carta"
                                                    wire:click="mountAction('removeItem', { item_id: {{ $item->id }} })"
                                                />
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