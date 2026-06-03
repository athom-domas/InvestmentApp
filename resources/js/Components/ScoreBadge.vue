<script setup>
import { computed } from 'vue'

const props = defineProps({
    score: { type: Number, required: true },
    showLabel: { type: Boolean, default: false },
    size: { type: String, default: 'sm' },
})

const n = computed(() => Number(props.score))

const tier = computed(() => {
    if (n.value >= 80) return { css: 'bg-indigo-600 text-white', label: 'Molto interessante da analizzare' }
    if (n.value >= 65) return { css: 'bg-blue-500 text-white', label: 'Interessante' }
    if (n.value >= 50) return { css: 'bg-slate-200 text-slate-700', label: 'Neutrale' }
    return { css: 'bg-slate-100 text-slate-500', label: 'Debole' }
})

const sizeClass = computed(() =>
    props.size === 'lg' ? 'px-3 py-1 text-base font-bold' : 'px-2 py-0.5 text-sm font-semibold'
)
</script>

<template>
    <span class="inline-flex items-center gap-2">
        <span :class="['rounded tabular-nums', tier.css, sizeClass]">
            {{ n.toFixed(1) }}
        </span>
        <span v-if="showLabel" class="text-sm text-slate-500">{{ tier.label }}</span>
    </span>
</template>
