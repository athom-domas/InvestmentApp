<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({
    portfolios: { type: Array, default: () => [] },
})

const showForm = ref(false)
const form = useForm({ name: '', base_currency: 'EUR' })

const submit = () => {
    form.post(route('portfolios.store'), {
        onSuccess: () => {
            form.reset()
            showForm.value = false
        },
    })
}
</script>

<template>
    <Head title="Portafogli" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-800">I miei Portafogli</h1>
                <button
                    @click="showForm = !showForm"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors"
                >
                    + Nuovo portafoglio
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Create form -->
            <div v-if="showForm" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Nuovo portafoglio</h2>
                <form @submit.prevent="submit" class="flex flex-wrap gap-3">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nome portafoglio"
                        maxlength="100"
                        required
                        class="flex-1 min-w-48 rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    />
                    <select v-model="form.base_currency"
                        class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                        <option value="GBP">GBP</option>
                    </select>
                    <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50 transition-colors">
                        Crea
                    </button>
                    <button type="button" @click="showForm = false"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                        Annulla
                    </button>
                </form>
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <!-- List -->
            <div v-if="portfolios.length" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="divide-y divide-slate-50">
                    <Link
                        v-for="p in portfolios"
                        :key="p.id"
                        :href="route('portfolios.show', p.id)"
                        class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition-colors"
                    >
                        <div>
                            <p class="font-medium text-slate-800">{{ p.name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ p.positions_count }} posizioni · {{ p.base_currency }}
                            </p>
                        </div>
                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </Link>
                </div>
            </div>

            <EmptyState v-else title="Nessun portafoglio" description="Crea il primo portafoglio per tracciare le posizioni.">
                <button v-if="!showForm" @click="showForm = true"
                    class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                    Crea portafoglio
                </button>
            </EmptyState>
        </div>
    </AuthenticatedLayout>
</template>
