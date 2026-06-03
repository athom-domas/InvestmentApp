<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Cerca ticker o nome...' },
})

const emit = defineEmits(['update:modelValue'])

const local = ref(props.modelValue)
let timer = null

watch(local, (val) => {
    clearTimeout(timer)
    timer = setTimeout(() => emit('update:modelValue', val), 350)
})

watch(() => props.modelValue, (val) => {
    if (val !== local.value) local.value = val
})
</script>

<template>
    <div class="relative">
        <svg
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input
            v-model="local"
            type="text"
            :placeholder="placeholder"
            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
        />
    </div>
</template>
