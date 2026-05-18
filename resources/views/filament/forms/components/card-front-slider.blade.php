<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ activeSlide: 0 }"
        class="space-y-4"
    >
        <div class="flex gap-2">
            <button
                type="button"
                x-on:click="activeSlide = 0"
                x-bind:class="activeSlide === 0 ? 'border border-primary-600' : 'border-white/5 text-gray-400'"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
            >
                Hook
            </button>

            <button
                type="button"
                x-on:click="activeSlide = 1"
                x-bind:class="activeSlide === 1 ? 'border border-primary-600' : 'border-white/5 text-gray-400'"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
            >
                Idea
            </button>
        </div>

        <div class="relative min-h-[240px] overflow-hidden">
            {{-- HOOK FIELDS --}}
            <section class="absolute inset space-y-4 w-full shrink-0"
                x-show="activeSlide === 0"
                x-transition.opacity.duration.150ms
                class="space-y-4"
            >
                <div>
                    <label class="text-sm font-semibold">
                        Hook desde biblioteca
                    </label>

                    <select
                        wire:model="mountedActionsData.0.hook_id"
                        class="mt-2 w-full rounded-lg border border-black/10 dark:border-white/10 px-3 py-2 text-sm"
                    >
                        <option value="">Seleccione una opción</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold">
                        Hook
                    </label>

                    <textarea
                        wire:model="mountedActionsData.0.hook_text"
                        rows="3"
                        class="bg-gray-400/10 mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm
                                outline-none
                                focus:border-primary-500
                                focus:ring-1 focus:ring-primary-500
                                focus:ring-offset-0
                        "
                    ></textarea>
                </div>
            </section>

            {{-- IDEA FIELDS --}}
            <section class="absolute inset space-y-4 w-full shrink-0"
                x-show="activeSlide === 1"
                x-transition.opacity.duration.150ms
            >
                <div>
                    <label class="text-sm font-semibold">
                        Contexto
                    </label>

                    <select
                        wire:model="mountedActionsData.0.idea_context"
                        class="mt-2 w-full rounded-lg border border-black/10 dark:border-white/10 px-3 py-2 text-sm"
                    >
                        <option value="">Seleccione una opción</option>
                        <option value="mainstream">Mainstream</option>
                        <option value="underground">Underground</option>
                        <option value="technical">Técnico</option>
                        <option value="opinion">Opinión</option>
                        <option value="culture">Cultura</option>
                        <option value="internet_niche">Internet + nicho</option>
                        <option value="broad_reading">Lectura amplia</option>
                        <option value="off_topic">Fuera de tema</option>
                    </select>

                    <p class="mt-2 text-sm text-gray-400">
                        Elige un ángulo para que puedas ubicarla dentro de tu nicho.
                    </p>
                </div>

                <div>
                    <label class="text-sm font-semibold">
                        Idea
                    </label>

                    <textarea
                        wire:model="mountedActionsData.0.idea_text"
                        rows="3"
                        class="bg-gray-400/10 mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm
                            outline-none
                            focus:border-primary-500
                            focus:ring-1 focus:ring-primary-500
                            focus:ring-offset-0
                        "
                    ></textarea>
                </div>
            </section>
        </div>
    </div>
</x-dynamic-component>