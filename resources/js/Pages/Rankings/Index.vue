<script setup>
import { reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ScoreBadge from '@/Components/ScoreBadge.vue'
import FactorBar from '@/Components/FactorBar.vue'
import Pagination from '@/Components/Pagination.vue'
import EmptyState from '@/Components/EmptyState.vue'
import SecuritySearchInput from '@/Components/SecuritySearchInput.vue'

const props = defineProps({
    rankings: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    sectors: { type: Array, default: () => [] },
    exchanges: { type: Array, default: () => [] },
    modelRun: { type: Object, default: null },
})

const SORT_OPTIONS = [
    { value: 'final_score',             label: 'Score Finale' },
    { value: 'quality_score',           label: 'Qualità' },
    { value: 'value_score',             label: 'Valore' },
    { value: 'growth_score',            label: 'Crescita' },
    { value: 'momentum_score',          label: 'Momentum' },
    { value: 'financial_strength_score', label: 'Solidità' },
    { value: 'risk_score',              label: 'Rischio' },
]

const form = reactive({
    search:      props.filters.search      ?? '',
    sector_id:   props.filters.sector_id   ?? '',
    exchange_id: props.filters.exchange_id ?? '',
    min_score:   props.filters.min_score   ?? '',
    sort:        props.filters.sort        ?? 'final_score',
})

const applyFilters = () => {
    const params = Object.fromEntries(
        Object.entries({
            search:      form.search      || null,
            sector_id:   form.sector_id   || null,
            exchange_id: form.exchange_id || null,
            min_score:   form.min_score   || null,
            sort:        form.sort !== 'final_score' ? form.sort : null,
        }).filter(([, v]) => v !== null)
    )
    router.get(route('rankings.index'), params, { preserveScroll: true, replace: true })
}

let searchTimer = null
watch(() => form.search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
})

watch(
    [() => form.sector_id, () => form.exchange_id, () => form.min_score, () => form.sort],
    applyFilters,
)

const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' })
    : null

const fmtScore = (v) => (v !== null && v !== undefined) ? Number(v).toFixed(0) : '—'
</script>

<template>
    <Head title="Ranking" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Ranking</h1>
                    <p v-if="modelRun" class="mt-0.5 text-sm text-slate-500">
                        Aggiornato il {{ formatDate(modelRun.finished_at) }} · v{{ modelRun.model_version }}
                    </p>
                </div>
                <span v-if="rankings" class="text-sm text-slate-500">
                    {{ rankings.total }} azioni
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <SecuritySearchInput v-model="form.search" class="lg:col-span-2" />

                    <select v-model="form.sector_id"
                        class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="">Tutti i settori</option>
                        <option v-for="s in sectors" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>

                    <select v-model="form.exchange_id"
                        class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="">Tutte le borse</option>
                        <option v-for="e in exchanges" :key="e.id" :value="e.id">{{ e.code }}</option>
                    </select>

                    <select v-model="form.sort"
                        class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option v-for="o in SORT_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </div>
                <div class="mt-3 flex items-center gap-3">
                    <label class="text-sm text-slate-600">Score minimo</label>
                    <input
                        v-model="form.min_score"
                        type="number" min="0" max="100" step="5"
                        placeholder="0"
                        class="w-20 rounded-lg border border-slate-200 bg-white py-1.5 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    />
                </div>
            </div>

            <!-- No model run -->
            <EmptyState v-if="!modelRun" title="Nessun modello disponibile"
                description="Esegui il comando scoring:run per generare i ranking." />

            <!-- Table -->
            <div v-else-if="rankings && rankings.data.length" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 text-center w-12">#</th>
                                <th class="px-4 py-3 text-left">Ticker</th>
                                <th class="hidden px-4 py-3 text-left md:table-cell">Nome</th>
                                <th class="hidden px-4 py-3 text-left sm:table-cell">Exchange</th>
                                <th class="hidden px-4 py-3 text-left lg:table-cell">Settore</th>
                                <th class="px-4 py-3 text-right">Score</th>
                                <th class="hidden px-4 py-3 text-right xl:table-cell">Qualità</th>
                                <th class="hidden px-4 py-3 text-right xl:table-cell">Valore</th>
                                <th class="hidden px-4 py-3 text-right xl:table-cell">Crescita</th>
                                <th class="hidden px-4 py-3 text-right xl:table-cell">Momentum</th>
                                <th class="hidden px-4 py-3 text-right xl:table-cell">Solidità</th>
                                <th class="hidden px-4 py-3 text-right xl:table-cell">Rischio</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="r in rankings.data"
                                :key="r.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="px-4 py-3 text-center text-xs font-bold text-slate-400 tabular-nums">{{ r.rank }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-800">{{ r.security?.ticker }}</span>
                                </td>
                                <td class="hidden px-4 py-3 text-slate-500 md:table-cell">
                                    <span class="max-w-xs truncate block">{{ r.security?.name }}</span>
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">
                                        {{ r.security?.exchange?.code }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 text-xs text-slate-500 lg:table-cell">
                                    {{ r.security?.sector?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <ScoreBadge :score="r.final_score" />
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-xs text-slate-500 xl:table-cell">
                                    {{ fmtScore(r.quality_score) }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-xs text-slate-500 xl:table-cell">
                                    {{ fmtScore(r.value_score) }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-xs text-slate-500 xl:table-cell">
                                    {{ fmtScore(r.growth_score) }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-xs text-slate-500 xl:table-cell">
                                    {{ fmtScore(r.momentum_score) }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-xs text-slate-500 xl:table-cell">
                                    {{ fmtScore(r.financial_strength_score) }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-xs text-slate-500 xl:table-cell">
                                    {{ fmtScore(r.risk_score) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('securities.show', r.security_id)"
                                        class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">
                                        Dettaglio
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">
                    <Pagination :paginator="rankings" />
                </div>
            </div>

            <EmptyState v-else title="Nessun risultato" description="Prova a modificare i filtri di ricerca." />
        </div>
    </AuthenticatedLayout>
</template>
