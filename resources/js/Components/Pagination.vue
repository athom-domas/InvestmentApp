<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    paginator: { type: Object, required: true },
})
</script>

<template>
    <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm text-slate-500">
            <template v-if="paginator.total > 0">
                Mostrando {{ paginator.from }}–{{ paginator.to }} di {{ paginator.total }}
            </template>
            <template v-else>Nessun risultato</template>
        </p>
        <nav v-if="paginator.last_page > 1" class="flex flex-wrap gap-1">
            <template v-for="link in paginator.links" :key="link.label">
                <span
                    v-if="!link.url"
                    class="px-3 py-1.5 text-sm text-slate-300 select-none"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    :class="[
                        'px-3 py-1.5 text-sm rounded border transition-colors',
                        link.active
                            ? 'bg-slate-800 text-white border-slate-800'
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                    ]"
                    preserve-scroll
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
