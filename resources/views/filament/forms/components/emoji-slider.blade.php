@php
    $labels = $getEmojiLabels();
    $statePath = $getStatePath();
    $showOptionText = $shouldShowOptionText();
    $min = min(array_keys($labels));
    $max = max(array_keys($labels));
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ state: $wire.$entangle(@js($getStatePath())) }"
        labels: @js($labels),
        min: {{ $min }},
        max: {{ $max }},
        get label() {
            return this.labels[this.state] ?? 'Sin medir';
        }
        class="space-y-3"
        {{ $getExtraAttributeBag() }}
    >
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                <span x-text="label"></span>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
                <span x-text="state ?? '—'"></span>/{{ $max }}
            </div>
        </div>

        <input
            type="range"
            x-model.number="state"
            min="{{ $min }}"
            max="{{ $max }}"
            step="1"
            class="
                w-full cursor-pointer accent-primary-600
                [&::-webkit-slider-thumb]:cursor-pointer
                [&::-moz-range-thumb]:cursor-pointer
            "
        />

        <div class="flex justify-between">
            @foreach ($labels as $value => $label)
                @php
                    $parts = explode(' ', $label, 2);
                    $emoji = $parts[0] ?? $label;
                    $text = $parts[1] ?? '';
                @endphp

                <button
                    type="button"
                    x-on:click="state = {{ $value }}"
                    class="flex flex-col items-center gap-2 transition hover:scale-110"
                    title="{{ $label }}"
                >
                    <span class="text-2xl leading-none"
                    >
                        {{ $emoji }}
                    </span>

                    @if ($showOptionText)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $text }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
