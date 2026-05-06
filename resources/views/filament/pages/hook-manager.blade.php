<x-filament-panels::page>
    <div class="flex gap-2 border-b border-gray-200 dark:border-white/10">
        <button
            type="button"
            wire:click="setActiveTab('library')"
            class="px-4 py-2 text-sm font-medium border-b-2 transition
                {{ $activeTab === 'library'
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}"
        >
            Lista
        </button>

        <button
            type="button"
            wire:click="setActiveTab('groups')"
            class="px-4 py-2 text-sm font-medium border-b-2 transition
                {{ $activeTab === 'groups'
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}"
        >
            Grupos
        </button>
    </div>
    
    @if ($activeTab === 'groups')
        <div>
            <p class="text-sm text-gray-500">
                Pre-selecciona hooks y utilízalos en tu
                <a
                    href="{{ \App\Filament\Pages\CyclesManager::getUrl() }}"
                    class="font-medium text-primary-600 hover:text-primary-500"
                >
                    próxima baraja
                </a>
            </p>
        </div>
        
        <x-filament::section>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    {{-- <x-filament::section> --}}
                        <x-slot name="heading">
                            Mis grupos
                        </x-slot>
                                            
                        <button
                            type="button"
                            wire:click="toggleGroupSort"
                            class="inline-flex items-center gap-2 pb-4 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                            </svg>
    
                            <span>
                                {{ $groupSortDirection === 'desc' ? 'Más nuevos primero' : 'Más viejos primero' }}
                            </span>
                        </button>
        
                        <div class="space-y-2">
                            @forelse ($this->hookGroups as $group)
                                <button
                                    type="button"
                                    wire:click="selectGroup({{ $group->id }})"
                                    class="w-full rounded-xl border px-4 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-white/5
                                        {{ $selectedGroupId === $group->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : 'border-gray-200 dark:border-white/10' }}"
                                >
                                    <div class="font-medium">
                                        {{ $group->name }}
                                    </div>
        
                                    <div class="text-sm text-gray-500">
                                        {{ $group->hooks_count }} hooks
                                    </div>
                                </button>
                            @empty
                                <p class="text-sm text-gray-500">
                                    Todavía no tienes grupos.
                                </p>
                            @endforelse
                        </div>
                    {{-- </x-filament::section> --}}
                </div>
        
                <div class="lg:col-span-8">
                    <x-slot name="heading">
                        Hooks del grupo
                    </x-slot>
    
                    @if ($this->selectedGroup)
                        <div class="space-y-3">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold">
                                        {{ $this->selectedGroup->name }}
                                    </h2>
    
                                    @if ($this->selectedGroup->description)
                                        <p class="text-sm text-gray-500">
                                            {{ $this->selectedGroup->description }}
                                        </p>
                                    @endif
                                </div>
    
                                <div class="flex items-center gap-2">
                                    {{ $this->assignHooksAction }}
                                    {{ $this->deleteGroupAction }}
                                </div>
                            </div>
    
                            {{-- ACORDEÓN DE HOOKS --}}
                            <div class="space-y-2">
                                @if ($this->selectedGroup->hooks->isNotEmpty())
                                    <div
                                        x-data
                                        x-ref="hookList"
                                        x-init="
                                            new Sortable($refs.hookList, {
                                                animation: 150,
                                                handle: '[data-sortable-handle]',
                                                onEnd: () => {
                                                    const ids = Array.from($refs.hookList.children).map((item) => item.dataset.hookId)
    
                                                    $wire.reorderHooks(ids)
                                                },
                                            })
                                        "
                                        class="space-y-2"
                                    >
                                        @foreach ($this->selectedGroup->hooks as $hook)
                                            <div
                                                wire:key="hook-group-{{ $this->selectedGroup->id }}-hook-{{ $hook->id }}"
                                                data-hook-id="{{ $hook->id }}"
                                                class="rounded-xl border border-gray-200 p-4 dark:border-white/10"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <button
                                                        type="button"
                                                        data-sortable-handle
                                                        class="cursor-grab text-gray-400 hover:text-gray-700 active:cursor-grabbing dark:hover:text-gray-200"
                                                    >
                                                        @svg('icon-drag-icon', 'w-5 h-5 text-gray-500')
                                                    </button>
    
                                                    <div class="flex-1">
                                                        <button
                                                            type="button"
                                                            wire:click="toggleHookDetails({{ $hook->id }})"
                                                            class="flex w-full items-center justify-between gap-4 text-left"
                                                        >
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs text-gray-500">#{{ $hook->pivot->sort_order }}</span>
                                                                <div class="font-medium">
                                                                    {{ $hook->title ?? $hook->name ?? $hook->hook }}
                                                                </div>
                                                            </div>
    
                                                            <div class="text-xs text-gray-500">
                                                                {{ $expandedHookId === $hook->id ? 'Cerrar' : 'Ver detalles' }}
                                                            </div>
                                                        </button>
    
                                                        @if ($expandedHookId === $hook->id)
                                                            <div class="mt-3 pt-3 border-t border-gray-200 text-sm text-gray-600 dark:border-white/10 dark:text-gray-400">
                                                                @if (! empty($hook->description))
                                                                    {{-- dejar en una sola linea por la identación del <p> --}}
                                                                    <p class="whitespace-pre-line leading-6">{{ trim($hook->description) }}</p>
                                                                @else
                                                                    <p class="italic text-gray-400">
                                                                        Este hook no tiene descripción.
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">
                                        Este grupo todavía no tiene hooks.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">
                            Selecciona o crea un grupo de hooks.
                        </p>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    
    @if ($activeTab === 'library')
        <x-slot name="heading">
            Biblioteca
        </x-slot>

        <div>
            <p class="text-sm text-gray-500">
                Esta es tu librería de hooks. Aparecerán en todas tus
                <a
                    href="{{ \App\Filament\Pages\CyclesManager::getUrl() }}"
                    class="font-medium text-primary-600 hover:text-primary-500"
                >
                    barajas
                </a>
            </p>
        </div>

        {{ $this->table }}
    @endif

    <x-filament-actions::modals />

    @once
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endonce
</x-filament-panels::page>
