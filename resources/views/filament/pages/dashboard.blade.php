@php
    $pinnedCycleItems = $this->getPinnedCycleItems();
    $decks = $this->getDecks();
@endphp

<x-filament-panels::page>
    <div class="mx-auto max-w-5xl space-y-6">

        <section class="rounded-2xl border bg-white border-gray-200 p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-2xl">
                @livewire(\App\Filament\Widgets\QuickHookGenerator::class)
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            {{-- FAVORITES --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">
                        Favoritos
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tus combinaciones fijadas
                    </p>
                </div>

                @if ($pinnedCycleItems->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700">
                        <p class="text-sm font-medium">
                            No tienes favoritos todavía
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Fija tus mejores combinaciones para verlas aquí
                        </p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($pinnedCycleItems as $item)
                            <a
                                href="{{ \App\Filament\Pages\CycleBoard::getUrl(['cycle' => $item->cycle_id]) }}"
                                class="block rounded-xl border border-gray-200 px-4 py-3 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/5"
                            >
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $item->cycle?->name }}
                                </p>

                                <div class="mt-1 overflow-x-auto whitespace-nowrap pb-1 text-sm font-medium text-gray-950 dark:text-white">
                                    <span>
                                        {{ $item->hook?->name ?? 'Sin hook' }}
                                    </span>

                                    <span class="text-gray-400">+</span>

                                    @if ($item->idea)
                                        <span>
                                            {{ $item->idea->title }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">
                                            Sin idea asignada
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- DECKS --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Barajas
                        </h2>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Mazos de ideas para inspirarte
                        </p>
                    </div>

                    @if ($decks->isNotEmpty())
                        @if (app(\App\Services\PlanLimitService::class)->canCreateDeck(auth()->user()))
                            <a
                                href="{{ route('filament.admin.pages.cycles-manager') }}"
                                class="text-sm font-medium text-primary-600 hover:text-primary-500"
                            >
                                + Nueva
                            </a>
                        @else
                            <div class="flex justify-center">
                                <span
                                    wire:click="mountAction('subscribe')"
                                    class="cursor-pointer text-sm font-medium text-primary-600 hover:text-primary-500"
                                >
                                    ¿Necesitas más?
                                </span>
                            </div>
                        @endif
                    @endif
                </div>

                @if ($decks->isEmpty())
                    <div class="rounded-xl border border-dashed p-6 text-center dark:border-gray-700">
                        <p class="text-sm font-medium">
                            No tienes barajas todavía.
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Crea tu primera baraja para empezar.
                        </p>

                        <a
                            href="{{ route('filament.admin.pages.cycles-manager') }}"
                            class="mt-4 inline-flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium text-primary-50 shadow-none ring-1 ring-primary-600 transition hover:bg-primary-600"
                        >
                            @svg('icon-deck-icon', 'w-5 h-5') Crear baraja
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($decks as $deck)
                            <a
                                href="{{ \App\Filament\Pages\CycleBoard::getUrl(['cycle' => $deck->id]) }}"
                                class="block rounded-xl border p-4 border-gray-300 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                            >
                                <p class="text-sm font-medium">
                                    {{ $deck->name }}
                                </p>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $deck->items_count ?? 0 }} cartas
                                </p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>