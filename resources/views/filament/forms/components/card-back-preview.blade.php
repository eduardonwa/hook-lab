<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ flipped: true }"
        x-on:click="flipped = !flipped"
        class="group flex h-full w-full cursor-pointer items-center justify-center"
    >
        <div class="relative h-[390px] w-[250px] max-w-full">
            {{-- Frente --}}
            <div
                x-show="!flipped"
                x-transition.opacity.duration.150ms
                class="absolute inset-0 rounded-xl"
            >
                <div class="flex justify-center items-center">
                    <div class="absolute inset-0">
                        <img
                            src="{{ asset('images/card-3-reverse.svg') }}"
                            alt="Reverso de carta"
                            class="block h-full w-full rounded-3xl object-contain drop-shadow-lg"
                        >
                    </div>
                </div>
            </div>

            {{-- Reverso / descripción --}}
            <div
                x-show="flipped"
                x-transition.opacity.duration.150ms
                class="absolute inset-0 border dark:border-white/10 rounded-3xl bg-stone-200 dark:bg-transparent p-4 drop-shadow-lg"
            >
                <div class="card-scroll h-full overflow-y-auto">
                    <h3 class="mt-2 text-lg font-semibold dark:text-white">
                        {{ $this->editingTriggerName ?? '-' }}
                    </h3>
    
                    <p class="whitespace-pre-line mt-4 text-sm leading-relaxed dark:text-gray-300">{{ trim($this->cleanText($this->editingTriggerDescription)) ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>