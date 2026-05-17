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
                x-bind:class="activeSlide === 0 ? 'border border-primary-600 text-white' : 'border-white/5 text-gray-400'"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
            >
                Hook
            </button>

            <button
                type="button"
                x-on:click="activeSlide = 1"
                x-bind:class="activeSlide === 1 ? 'border border-primary-600 text-white' : 'border-white/5 text-gray-400'"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
            >
                Idea
            </button>
        </div>

        <div class="overflow-hidden">
            <div
                class="flex transition-transform duration-300 ease-out"
                x-bind:style="`transform: translateX(-${activeSlide * 100}%);`"
>
                <section class="w-full shrink-0 space-y-4">
                    {{-- HOOK FIELDS --}}
                    <div>
                        <label class="text-sm font-semibold text-white">
                            Hook desde biblioteca
                        </label>

                        <select
                            wire:model="mountedActionsData.0.hook_id"
                            class="mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm text-white"
                        >
                            <option value="">Seleccione una opción</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-white">
                            Hook
                        </label>

                        <textarea
                            wire:model="mountedActionsData.0.hook_text"
                            rows="3"
                            class="bg-gray-400/10 mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm text-white        
                                    outline-none
                                    focus:border-primary-500
                                    focus:ring-1 focus:ring-primary-500
                                    focus:ring-offset-0
                            "
                        ></textarea>
                    </div>
                </section>

                <section class="w-full shrink-0 space-y-4">
                    {{-- IDEA FIELDS --}}
                    <div>
                        <label class="text-sm font-semibold text-white">
                            Contexto
                        </label>

                        <select
                            wire:model="mountedActionsData.0.idea_context"
                            class="mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm text-white"
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
                        <label class="text-sm font-semibold text-white">
                            Idea
                        </label>

                        <textarea
                            wire:model="mountedActionsData.0.idea_text"
                            rows="3"
                            class="bg-gray-400/10 mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm text-white
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
    </div>
</x-dynamic-component>