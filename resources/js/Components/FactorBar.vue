<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: null },
})

const n = computed(() => (props.value !== null && props.value !== undefined) ? Number(props.value) : null)
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="w-36 shrink-0 text-sm text-slate-600">{{ label }}</div>
        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
            <div
                v-if="n !== null"
                class="h-full rounded-full bg-indigo-500 transition-all duration-300"
                :style="{ width: `${Math.max(0, Math.min(100, n))}%` }"
            />
        </div>
        <div class="w-10 shrink-0 text-right text-sm font-medium text-slate-700 tabular-nums">
            {{ n !== null ? n.toFixed(0) : '—' }}
        </div>
    </div>
</template>
