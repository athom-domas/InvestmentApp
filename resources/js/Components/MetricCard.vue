<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-800 tabular-nums">{{ value }}</p>
        <p v-if="subtitle" class="mt-0.5 text-xs text-slate-400">{{ subtitle }}</p>
    </div>
</template>
