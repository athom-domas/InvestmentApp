<script setup>
defineProps({
    risks: { type: String, default: '' },
})
</script>

<template>
    <div v-if="risks" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-amber-700">Rischi da valutare</p>
        <p class="text-sm text-amber-800 whitespace-pre-line leading-relaxed">{{ risks }}</p>
    </div>
</template>
