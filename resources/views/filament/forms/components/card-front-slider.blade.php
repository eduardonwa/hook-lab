<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="space-y-5">
        <fieldset>
            <legend class="text-purple-300 text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">
                Idea
            </legend>

            <div class="space-y-5">
                <div>
                    <label class="block mt-2 text-sm font-semibold">
                        Contexto
                    </label>

                    <p class="mt-2 text-sm text-gray-400">
                        Elige un ángulo para que puedas ubicarla dentro de tu nicho.
                    </p>

                    <select
                        wire:model="mountedActions.0.data.idea_context"
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
                </div>

                <div>
                    <label class="text-sm font-semibold">
                        Idea
                    </label>

                    <textarea
                        wire:model="mountedActions.0.data.idea_text"
                        rows="3"
                        class="bg-gray-400/10 mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:ring-offset-0"
                    ></textarea>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend class="text-purple-300 text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">
                Hook
            </legend>

            <div class="space-y-5">
                <div>
                    <label class="block mt-2 text-sm font-semibold">
                        Desde biblioteca
                    </label>

                    <select
                        wire:model="mountedActions.0.data.hook_id"
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
                        wire:model="mountedActions.0.data.hook_text"
                        rows="3"
                        class="bg-gray-400/10 mt-2 w-full rounded-lg border border-white/10 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:ring-offset-0"
                    ></textarea>
                </div>
            </div>
        </fieldset>
    </div>
</x-dynamic-component>