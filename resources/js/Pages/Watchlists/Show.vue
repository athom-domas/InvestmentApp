<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ScoreBadge from '@/Components/ScoreBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({
    watchlist:    { type: Object, required: true },
    rankings:     { type: Object, default: () => ({}) },
    allSecurities: { type: Array, default: () => [] },
})

const showAddForm = ref(false)
const addForm = useForm({ security_id: '', notes: '' })

const addItem = () => {
    addForm.post(route('watchlists.items.store', props.watchlist.id), {
        onSuccess: () => {
            addForm.reset()
            showAddForm.value = false
        },
    })
}

const removeItem = (securityId) => {
    router.delete(route('watchlists.items.destroy', [props.watchlist.id, securityId]), {
        preserveScroll: true,
    })
}

const getRanking = (securityId) => props.rankings[securityId] ?? null
</script>

<template>
    <Head :title="`Watchlist: ${watchlist.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('watchlists.index')" class="text-sm text-slate-400 hover:text-slate-600">
                            Watchlist
                        </Link>
                        <span class="text-slate-300">/</span>
                        <h1 class="text-xl font-bold text-slate-800">{{ watchlist.name }}</h1>
                    </div>
                    <p class="mt-0.5 text-sm text-slate-500">{{ watchlist.securities.length }} azioni</p>
                </div>
                <button
                    @click="showAddForm = !showAddForm"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors"
                >
                    + Aggiungi azione
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Add form -->
            <div v-if="showAddForm" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Aggiungi azione</h2>
                <form @submit.prevent="addItem" class="space-y-3">
                    <div class="flex gap-3">
                        <select
                            v-model="addForm.security_id"
                            required
                            class="flex-1 rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">Seleziona un'azione...</option>
                            <option v-for="s in allSecurities" :key="s.id" :value="s.id">
                                {{ s.ticker }} — {{ s.name }} ({{ s.exchange?.code }})
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <input
                            v-model="addForm.notes"
                            type="text"
                            placeholder="Note (opzionale)"
                            class="flex-1 rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        />
                        <button type="submit" :disabled="addForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50 transition-colors">
                            Aggiungi
                        </button>
                        <button type="button" @click="showAddForm = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                            Annulla
                        </button>
                    </div>
                    <p v-if="addForm.errors.security_id" class="text-xs text-red-500">{{ addForm.errors.security_id }}</p>
                </form>
            </div>

            <!-- Securities list -->
            <div v-if="watchlist.securities.length" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="divide-y divide-slate-50">
                    <div
                        v-for="s in watchlist.securities"
                        :key="s.id"
                        class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50 transition-colors"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <Link :href="route('securities.show', s.id)"
                                    class="font-semibold text-slate-800 hover:text-indigo-600">
                                    {{ s.ticker }}
                                </Link>
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-500">
                                    {{ s.exchange?.code }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ s.name }}</p>
                            <p v-if="s.pivot?.notes" class="mt-0.5 text-xs text-slate-400">{{ s.pivot.notes }}</p>
                        </div>
                        <div class="shrink-0">
                            <ScoreBadge v-if="getRanking(s.id)" :score="getRanking(s.id).final_score" />
                            <span v-else class="text-xs text-slate-400">N/D</span>
                        </div>
                        <div class="shrink-0 text-xs text-slate-400 hidden sm:block tabular-nums">
                            <span v-if="getRanking(s.id)">#{{ getRanking(s.id).rank }}</span>
                        </div>
                        <button
                            @click="removeItem(s.id)"
                            class="shrink-0 rounded p-1 text-slate-300 hover:bg-red-50 hover:text-red-500 transition-colors"
                            title="Rimuovi"
                        >
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <EmptyState v-else title="Watchlist vuota" description="Aggiungi le azioni che vuoi monitorare.">
                <button v-if="!showAddForm" @click="showAddForm = true"
                    class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                    Aggiungi azione
                </button>
            </EmptyState>
        </div>
    </AuthenticatedLayout>
</template>
