<div class="space-y-4">
    <div class="flex items-center gap-3 text-sm text-gray-500">
        <span>{{ $cycle->items->count() }} cartas en la baraja</span>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
        <div class="max-h-[48vh] overflow-y-auto">
            <div class="overflow-x-auto">
                <table class="min-w-[520px] w-full table-fixed text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-950">
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="w-16 p-3 text-left">#</th>
                            <th class="w-56 p-3 text-left">Trigger</th>
                            <th class="p-3 text-left">Hook</th>
                            <th class="p-3 text-left">Idea</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($cycle->items->sortBy('position') as $item)
                            <tr class="border-t border-gray-200 dark:border-white/10">
                                <td class="w-16 p-3 text-gray-500 dark:text-gray-400">
                                    {{ $item->position }}
                                </td>

                                <td class="w-56 p-3 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item->trigger?->name ?? '-' }}
                                </td>

                                <td class="w-56 p-3 text-gray-500 dark:text-gray-400">
                                    {{ $item->hook_text ?? $item->hook?->name ?? '-' }}
                                </td>

                                <td class="p-3 text-gray-500 dark:text-gray-400">
                                    {{ $item->idea_text ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                    Esta baraja todavía no tiene cartas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>