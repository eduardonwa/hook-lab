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
        x-data="{
            state: $wire.$entangle(@js($statePath)),
            labels: @js($labels),
            min: @js($min),
            max: @js($max),
            get label() {
                return this.labels[this.state] ?? 'Sin medir';
            },
        }"
        class="space-y-2"
        {{ $getExtraAttributeBag() }}
    >
        <div class="flex items-center justify-between border-b border-white/10 pb-2">
            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">
                Estado
            </span>

            <span class="text-xs font-mono text-gray-500">
                <span x-text="state ?? '—'"></span>/{{ $max }}
            </span>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-semibold text-white">
                <span x-text="label"></span>
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

        <div class="grid grid-cols-5 gap-1 pt-1">
            @foreach ($labels as $value => $label)
                @php
                    $parts = explode(' ', $label, 2);
                    $text = $parts[1] ?? $label;
                @endphp

                <button
                    type="button"
                    x-on:click="state = {{ $value }}"
                    x-bind:class="Number(state) === {{ $value }}
                        ? 'border-primary-500 bg-primary-500/10 text-white'
                        : 'border-white/10 text-gray-500 hover:border-primary-500 hover:text-white'"
                    class="rounded-md border border-white/10 px-2 py-1.5 text-center text-[10px] uppercase tracking-wide text-gray-500 transition hover:border-primary-500 hover:text-white"
                    title="{{ $label }}"
                >
                    {{ $text }}
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>