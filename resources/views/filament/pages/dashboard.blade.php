@php
    $pinnedCycleItems = $this->getPinnedCycleItems();
    $decks = $this->getDecks();
@endphp

<x-filament-panels::page>
    <div class="mx-auto max-w-5xl space-y-6">

        <section class="rounded-2xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-2xl">
                @livewire(\App\Filament\Widgets\QuickHookGenerator::class)
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">

            <section class="rounded-2xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">
                        Favoritos
                    </h2>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tus combinaciones fijadas
                    </p>
                </div>

                @if ($pinnedCycleItems->isEmpty())
                    <div class="rounded-xl border border-dashed p-6 text-center dark:border-gray-700">
                        <p class="text-sm font-medium">
                            No tienes favoritos todavía
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Fija tus mejores combinaciones para verlas aquí
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($pinnedCycleItems as $item)
                            <article class="rounded-xl border p-4 dark:border-gray-700">
                                <p class="text-sm font-medium">
                                    {{ $item->hook }}
                                </p>

                                @if ($item->idea)
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->idea }}
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
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
                        <a
                            href="{{ route('filament.admin.pages.cycles-manager') }}"
                            class="text-sm font-medium text-primary-600 hover:text-primary-500"
                        >
                            + Nueva
                        </a>
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
                            class="mt-4 inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500"
                        >
                            Crear baraja
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($decks as $deck)
                            <a
                                href="{{ route('filament.admin.pages.cycles-manager') }}"
                                class="block rounded-xl border p-4 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                            >
                                <p class="text-sm font-medium">
                                    {{ $deck->name }}
                                </p>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $deck->cycle_items_count ?? 0 }} items
                                </p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-filament-panels::page>