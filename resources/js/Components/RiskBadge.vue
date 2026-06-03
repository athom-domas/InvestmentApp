<script setup>
defineProps({
    risks: { type: Array, default: () => [] },
})
</script>

<template>
    <div v-if="risks?.length" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700">Rischi da valutare</p>
        <ul class="space-y-1.5">
            <li v-for="(r, i) in risks" :key="i" class="flex items-start gap-2 text-sm text-amber-800">
                <span class="mt-0.5 shrink-0 text-amber-500">•</span>
                <span>{{ r }}</span>
            </li>
        </ul>
    </div>
</template>
