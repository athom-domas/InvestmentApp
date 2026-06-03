<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ScoreBadge from '@/Components/ScoreBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({
    portfolio:     { type: Object, required: true },
    latestPrices:  { type: Object, default: () => ({}) },
    rankings:      { type: Object, default: () => ({}) },
    analytics:     { type: Object, required: true },
    allSecurities: { type: Array, default: () => [] },
})

const showAddForm = ref(false)
const editingPosition = ref(null)

const addForm = useForm({
    security_id:   '',
    quantity:      '',
    average_price: '',
    currency:      props.portfolio.base_currency ?? 'EUR',
    opened_at:     '',
    notes:         '',
})

const editForm = useForm({
    quantity:      '',
    average_price: '',
    currency:      '',
    opened_at:     '',
    notes:         '',
})

const addPosition = () => {
    addForm.post(route('portfolios.positions.store', props.portfolio.id), {
        onSuccess: () => {
            addForm.reset()
            addForm.currency = props.portfolio.base_currency ?? 'EUR'
            showAddForm.value = false
        },
    })
}

const startEdit = (position) => {
    editingPosition.value = position.id
    editForm.quantity      = position.quantity
    editForm.average_price = position.average_price ?? ''
    editForm.currency      = position.currency ?? props.portfolio.base_currency ?? 'EUR'
    editForm.opened_at     = position.opened_at ?? ''
    editForm.notes         = position.notes ?? ''
}

const saveEdit = (positionId) => {
    editForm.put(route('portfolios.positions.update', [props.portfolio.id, positionId]), {
        preserveScroll: true,
        onSuccess: () => { editingPosition.value = null },
    })
}

const removePosition = (positionId) => {
    router.delete(route('portfolios.positions.destroy', [props.portfolio.id, positionId]), {
        preserveScroll: true,
    })
}

const getLatestPrice = (securityId) => props.latestPrices[securityId] ?? null

const getRanking = (securityId) => props.rankings[securityId] ?? null

const getScore = (securityId) => {
    const r = getRanking(securityId)
    return r ? Number(r.final_score) : null
}

// Used only in the inline-edit row for real-time display
const livePositionValue = (position) => {
    const price = getLatestPrice(position.security_id)
    if (!price) return null
    return Number(position.quantity) * Number(price)
}

const formatNum = (n, decimals = 2) => {
    if (n === null || n === undefined || n === '') return '—'
    return Number(n).toLocaleString('it-IT', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
}

const fmtPct = (n) => {
    if (n === null || n === undefined) return '—'
    return Number(n).toFixed(1) + '%'
}

const fmtScore = (n) => {
    if (n === null || n === undefined) return '—'
    return Number(n).toFixed(0)
}
</script>

<template>
    <Head :title="`Portafoglio: ${portfolio.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('portfolios.index')" class="text-sm text-slate-400 hover:text-slate-600">
                            Portafogli
                        </Link>
                        <span class="text-slate-300">/</span>
                        <h1 class="text-xl font-bold text-slate-800">{{ portfolio.name }}</h1>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                        <span>{{ analytics.positionCount }} posizioni</span>
                        <span v-if="analytics.totalValue" class="font-medium text-slate-700">
                            Valore stimato: {{ formatNum(analytics.totalValue) }} {{ portfolio.base_currency }}
                        </span>
                        <span v-if="analytics.weightedScore" class="inline-flex items-center gap-1.5">
                            Score medio:
                            <ScoreBadge :score="analytics.weightedScore" size="sm" />
                        </span>
                    </div>
                </div>
                <button
                    @click="showAddForm = !showAddForm"
                    class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors"
                >
                    + Aggiungi posizione
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Add position form -->
            <div v-if="showAddForm" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Nuova posizione</h2>
                <form @submit.prevent="addPosition" class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <select v-model="addForm.security_id" required
                            class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Seleziona azione...</option>
                            <option v-for="s in allSecurities" :key="s.id" :value="s.id">
                                {{ s.ticker }} — {{ s.name }} ({{ s.exchange?.code }})
                            </option>
                        </select>
                        <input v-model="addForm.quantity" type="number" step="any" min="0.000001"
                            placeholder="Quantità *" required
                            class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                        <input v-model="addForm.average_price" type="number" step="any" min="0"
                            placeholder="Prezzo medio"
                            class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                        <select v-model="addForm.currency"
                            class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                            <option value="GBP">GBP</option>
                        </select>
                        <input v-model="addForm.opened_at" type="date"
                            class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <input v-model="addForm.notes" type="text" placeholder="Note (opzionale)"
                            class="flex-1 min-w-0 rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                        <button type="submit" :disabled="addForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50 transition-colors">
                            Aggiungi
                        </button>
                        <button type="button" @click="showAddForm = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                            Annulla
                        </button>
                    </div>
                    <div class="space-y-0.5">
                        <p v-if="addForm.errors.security_id" class="text-xs text-red-500">{{ addForm.errors.security_id }}</p>
                        <p v-if="addForm.errors.quantity" class="text-xs text-red-500">{{ addForm.errors.quantity }}</p>
                    </div>
                </form>
            </div>

            <!-- Empty state -->
            <EmptyState v-if="!portfolio.positions.length" title="Nessuna posizione"
                description="Aggiungi le tue posizioni per analizzare la composizione del portafoglio.">
                <button v-if="!showAddForm" @click="showAddForm = true"
                    class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                    Aggiungi posizione
                </button>
            </EmptyState>

            <template v-else>

                <!-- Positions table -->
                <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3 text-left">Azione</th>
                                    <th class="px-4 py-3 text-right">Quantità</th>
                                    <th class="hidden px-4 py-3 text-right sm:table-cell">P. medio</th>
                                    <th class="hidden px-4 py-3 text-right sm:table-cell">Ultimo</th>
                                    <th class="px-4 py-3 text-right">Valore</th>
                                    <th class="hidden px-4 py-3 text-right md:table-cell">Peso</th>
                                    <th class="hidden px-4 py-3 text-left lg:table-cell">Settore</th>
                                    <th class="hidden px-4 py-3 text-right xl:table-cell">Score</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template v-for="p in portfolio.positions" :key="p.id">

                                    <!-- View row -->
                                    <tr v-if="editingPosition !== p.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <Link :href="route('securities.show', p.security_id)"
                                                class="font-semibold text-slate-800 hover:text-indigo-600">
                                                {{ p.security?.ticker }}
                                            </Link>
                                            <p class="text-xs text-slate-400 truncate max-w-[12rem]">{{ p.security?.name }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-700">
                                            {{ formatNum(p.quantity, 4) }}
                                        </td>
                                        <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 sm:table-cell">
                                            {{ formatNum(p.average_price) }}
                                        </td>
                                        <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 sm:table-cell">
                                            {{ formatNum(getLatestPrice(p.security_id)) }}
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums font-medium text-slate-800">
                                            {{ formatNum(p.estimated_value) }}
                                        </td>
                                        <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 md:table-cell">
                                            {{ fmtPct(p.weight) }}
                                        </td>
                                        <td class="hidden px-4 py-3 text-xs text-slate-500 lg:table-cell">
                                            {{ p.security?.sector?.name ?? '—' }}
                                        </td>
                                        <td class="hidden px-4 py-3 text-right xl:table-cell">
                                            <ScoreBadge v-if="getScore(p.security_id) !== null"
                                                :score="getScore(p.security_id)" />
                                            <span v-else class="text-xs text-slate-400">N/D</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <button @click="startEdit(p)"
                                                    class="text-xs text-slate-400 hover:text-indigo-600 transition-colors">
                                                    Modifica
                                                </button>
                                                <button @click="removePosition(p.id)"
                                                    class="text-xs text-slate-400 hover:text-red-500 transition-colors">
                                                    Rimuovi
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit row -->
                                    <tr v-else class="bg-indigo-50">
                                        <td class="px-4 py-3 font-semibold text-slate-800">
                                            {{ p.security?.ticker }}
                                            <p class="text-xs font-normal text-slate-400">{{ p.security?.name }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input v-model="editForm.quantity" type="number" step="any" min="0.000001"
                                                class="w-24 rounded border border-slate-200 py-1 px-2 text-sm text-right tabular-nums text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-300" />
                                        </td>
                                        <td class="hidden px-4 py-3 sm:table-cell">
                                            <input v-model="editForm.average_price" type="number" step="any" min="0"
                                                class="w-24 rounded border border-slate-200 py-1 px-2 text-sm text-right tabular-nums text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-300" />
                                        </td>
                                        <td class="hidden px-4 py-3 text-right tabular-nums text-slate-500 text-sm sm:table-cell">
                                            {{ formatNum(getLatestPrice(p.security_id)) }}
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-500 text-sm">
                                            {{ formatNum(livePositionValue(p)) }}
                                        </td>
                                        <td class="hidden px-4 py-3 tabular-nums text-slate-500 text-sm text-right md:table-cell">
                                            {{ fmtPct(p.weight) }}
                                        </td>
                                        <td class="hidden px-4 py-3 lg:table-cell">
                                            <select v-model="editForm.currency"
                                                class="rounded border border-slate-200 py-1 px-2 text-xs text-slate-800 focus:outline-none">
                                                <option value="EUR">EUR</option>
                                                <option value="USD">USD</option>
                                                <option value="GBP">GBP</option>
                                            </select>
                                        </td>
                                        <td class="hidden px-4 py-3 xl:table-cell"></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-3">
                                                <button @click="saveEdit(p.id)"
                                                    :disabled="editForm.processing"
                                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-500 disabled:opacity-50">
                                                    Salva
                                                </button>
                                                <button @click="editingPosition = null"
                                                    class="text-xs text-slate-400 hover:text-slate-600">
                                                    Annulla
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Concentration warnings -->
                <div v-if="analytics.warnings.length"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-amber-800">
                        <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        Concentrazione
                    </h2>
                    <ul class="space-y-2">
                        <li v-for="w in analytics.warnings" :key="w"
                            class="text-sm text-amber-700">
                            {{ w }}
                        </li>
                    </ul>
                </div>

                <!-- Exposure grid -->
                <div class="grid gap-5 md:grid-cols-3">

                    <!-- By sector -->
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Per settore
                        </h2>
                        <div v-if="analytics.bySector.length" class="space-y-2.5">
                            <div v-for="item in analytics.bySector" :key="item.name"
                                class="flex items-center gap-2">
                                <span class="w-28 shrink-0 truncate text-xs text-slate-600">{{ item.name }}</span>
                                <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-indigo-400 transition-all"
                                        :style="{ width: item.weight + '%' }" />
                                </div>
                                <span class="w-11 shrink-0 text-right text-xs tabular-nums text-slate-700">
                                    {{ fmtPct(item.weight) }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400">Nessun dato settore disponibile.</p>
                    </div>

                    <!-- By exchange -->
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Per mercato
                        </h2>
                        <div v-if="analytics.byExchange.length" class="space-y-2.5">
                            <div v-for="item in analytics.byExchange" :key="item.name"
                                class="flex items-center gap-2">
                                <span class="w-28 shrink-0 truncate text-xs text-slate-600">{{ item.name }}</span>
                                <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-blue-400 transition-all"
                                        :style="{ width: item.weight + '%' }" />
                                </div>
                                <span class="w-11 shrink-0 text-right text-xs tabular-nums text-slate-700">
                                    {{ fmtPct(item.weight) }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400">Nessun dato mercato disponibile.</p>
                    </div>

                    <!-- By currency -->
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Per valuta
                        </h2>
                        <div v-if="analytics.byCurrency.length" class="space-y-2.5">
                            <div v-for="item in analytics.byCurrency" :key="item.name"
                                class="flex items-center gap-2">
                                <span class="w-28 shrink-0 truncate text-xs text-slate-600">{{ item.name }}</span>
                                <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-400 transition-all"
                                        :style="{ width: item.weight + '%' }" />
                                </div>
                                <span class="w-11 shrink-0 text-right text-xs tabular-nums text-slate-700">
                                    {{ fmtPct(item.weight) }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400">Nessun dato valuta disponibile.</p>
                    </div>

                </div>

            </template>

        </div>
    </AuthenticatedLayout>
</template>
