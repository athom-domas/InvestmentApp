<script setup>
import { computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ScoreBadge from '@/Components/ScoreBadge.vue'
import FactorBar from '@/Components/FactorBar.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import DisclaimerBox from '@/Components/DisclaimerBox.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({
    security:       { type: Object, required: true },
    modelRun:       { type: Object, default: null },
    ranking:        { type: Object, default: null },
    factorValues:   { type: Object, default: () => ({}) },
    fundamentals:   { type: Array, default: () => [] },
    priceBars:      { type: Array, default: () => [] },
    watchlists:     { type: Array, default: () => [] },
    inWatchlistIds: { type: Array, default: () => [] },
})

const FACTOR_LABELS = {
    quality:            'Qualità',
    value:              'Valore',
    growth:             'Crescita',
    momentum:           'Momentum',
    financial_strength: 'Solidità Finanziaria',
    risk:               'Rischio',
}

const factorScores = computed(() => {
    if (!props.ranking) return []
    return [
        { key: 'quality',            label: FACTOR_LABELS.quality,            score: props.ranking.quality_score },
        { key: 'value',              label: FACTOR_LABELS.value,              score: props.ranking.value_score },
        { key: 'growth',             label: FACTOR_LABELS.growth,             score: props.ranking.growth_score },
        { key: 'momentum',           label: FACTOR_LABELS.momentum,           score: props.ranking.momentum_score },
        { key: 'financial_strength', label: FACTOR_LABELS.financial_strength, score: props.ranking.financial_strength_score },
        { key: 'risk',               label: FACTOR_LABELS.risk,               score: props.ranking.risk_score },
    ]
})

const formatNum = (n, decimals = 2) => {
    if (n === null || n === undefined) return '—'
    return Number(n).toLocaleString('it-IT', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
}

const formatLarge = (n) => {
    if (!n) return '—'
    if (n >= 1e12) return (n / 1e12).toFixed(1) + 'T'
    if (n >= 1e9)  return (n / 1e9).toFixed(1) + 'B'
    if (n >= 1e6)  return (n / 1e6).toFixed(1) + 'M'
    return n.toLocaleString('it-IT')
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('it-IT') : '—'

const latestFundamentals = computed(() => props.fundamentals[0] ?? null)

const addForm = useForm({ security_id: props.security.id, notes: '' })

const addToWatchlist = (watchlistId) => {
    addForm.post(route('watchlists.items.store', watchlistId), {
        preserveScroll: true,
    })
}

const removeFromWatchlist = (watchlistId) => {
    router.delete(route('watchlists.items.destroy', [watchlistId, props.security.id]), {
        preserveScroll: true,
    })
}

const isInWatchlist = (watchlistId) => props.inWatchlistIds.includes(watchlistId)

const rankingReasons = computed(() => props.ranking?.metadata?.reasons ?? [])

const rankingRisks = computed(() => {
    if (!props.ranking) return []
    const meta = props.ranking.metadata
    if (Array.isArray(meta?.risks)) return meta.risks
    return (props.ranking.risks ?? '').split('\n').filter(Boolean)
})
</script>

<template>
    <Head :title="`${security.ticker} — ${security.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-slate-800">{{ security.ticker }}</h1>
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                            {{ security.exchange?.code }}
                        </span>
                        <span v-if="ranking" class="text-sm text-slate-500">Rank #{{ ranking.rank }}</span>
                    </div>
                    <p class="mt-1 text-slate-500">{{ security.name }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ security.sector?.name }}
                        <span v-if="security.industry"> · {{ security.industry.name }}</span>
                        <span v-if="security.country"> · {{ security.country }}</span>
                    </p>
                </div>
                <div v-if="ranking">
                    <ScoreBadge :score="ranking.final_score" :show-label="true" size="lg" />
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <DisclaimerBox />

            <!-- Watchlist panel -->
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Aggiungi alla watchlist</h2>

                <div v-if="!watchlists.length" class="flex items-center gap-2 text-sm text-slate-500">
                    Non hai ancora una watchlist.
                    <Link :href="route('watchlists.index')" class="text-indigo-600 hover:text-indigo-500">
                        Crea watchlist →
                    </Link>
                </div>

                <div v-else class="flex flex-wrap gap-2">
                    <template v-for="w in watchlists" :key="w.id">
                        <!-- Already in this watchlist -->
                        <span
                            v-if="isInWatchlist(w.id)"
                            class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 border border-indigo-200"
                        >
                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            {{ w.name }}
                            <button
                                @click="removeFromWatchlist(w.id)"
                                class="ml-0.5 rounded-full hover:bg-indigo-100 p-0.5 transition-colors"
                                title="Rimuovi"
                            >
                                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>

                        <!-- Not in this watchlist -->
                        <button
                            v-else
                            @click="addToWatchlist(w.id)"
                            :disabled="addForm.processing"
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-50 transition-colors"
                        >
                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ w.name }}
                        </button>
                    </template>
                </div>
            </div>

            <!-- No ranking -->
            <EmptyState v-if="!ranking" title="Nessun ranking disponibile"
                description="Questo titolo non è stato incluso nell'ultimo modello di scoring." />

            <template v-else>
                <!-- Summary + Risks -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <div v-if="ranking.summary" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Perché è emersa dallo screening
                        </h2>
                        <p class="mb-3 text-sm text-slate-700 leading-relaxed">{{ ranking.summary }}</p>
                        <ul v-if="rankingReasons.length" class="space-y-1.5">
                            <li
                                v-for="(reason, i) in rankingReasons"
                                :key="i"
                                class="flex items-start gap-2 text-sm text-slate-600"
                            >
                                <span class="mt-0.5 shrink-0 font-bold text-indigo-400">•</span>
                                <span>{{ reason }}</span>
                            </li>
                        </ul>
                    </div>

                    <RiskBadge :risks="rankingRisks" />
                </div>

                <!-- Factor breakdown -->
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Analisi fattoriale</h2>
                    <div class="space-y-3">
                        <FactorBar
                            v-for="f in factorScores"
                            :key="f.key"
                            :label="f.label"
                            :value="f.score"
                        />
                    </div>
                </div>
            </template>

            <!-- Fundamentals -->
            <div v-if="fundamentals.length" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-700">Dati fondamentali</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold text-slate-500">
                                <th class="px-4 py-3 text-left">Periodo</th>
                                <th class="px-4 py-3 text-right">Ricavi</th>
                                <th class="px-4 py-3 text-right">Utile netto</th>
                                <th class="px-4 py-3 text-right hidden md:table-cell">EBITDA</th>
                                <th class="px-4 py-3 text-right hidden sm:table-cell">P/E</th>
                                <th class="px-4 py-3 text-right hidden sm:table-cell">EV/EBITDA</th>
                                <th class="px-4 py-3 text-right hidden lg:table-cell">P/S</th>
                                <th class="px-4 py-3 text-right hidden lg:table-cell">P/B</th>
                                <th class="px-4 py-3 text-right hidden xl:table-cell">ROE</th>
                                <th class="px-4 py-3 text-right hidden xl:table-cell">Marg. Netto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="f in fundamentals" :key="f.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-700">
                                    {{ f.fiscal_period }} {{ f.fiscal_year }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ formatLarge(f.revenue) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ formatLarge(f.net_income) }}</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 md:table-cell">{{ formatLarge(f.ebitda) }}</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 sm:table-cell">{{ formatNum(f.pe_ratio, 1) }}</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 sm:table-cell">{{ formatNum(f.ev_ebitda, 1) }}</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 lg:table-cell">{{ formatNum(f.price_to_sales, 1) }}</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 lg:table-cell">{{ formatNum(f.price_to_book, 1) }}</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 xl:table-cell">{{ formatNum(f.return_on_equity, 1) }}%</td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-slate-600 xl:table-cell">{{ formatNum(f.net_margin, 1) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Price bars -->
            <div v-if="priceBars.length" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-700">
                        Prezzi recenti
                        <span class="ml-1 text-xs font-normal text-slate-400">(ultimi {{ priceBars.length }} giorni di trading)</span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold text-slate-500">
                                <th class="px-4 py-3 text-left">Data</th>
                                <th class="px-4 py-3 text-right">Apertura</th>
                                <th class="px-4 py-3 text-right hidden sm:table-cell">Max</th>
                                <th class="px-4 py-3 text-right hidden sm:table-cell">Min</th>
                                <th class="px-4 py-3 text-right">Chiusura</th>
                                <th class="px-4 py-3 text-right hidden md:table-cell">Volume</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="bar in [...priceBars].reverse().slice(0, 30)"
                                :key="bar.date"
                                class="hover:bg-slate-50"
                            >
                                <td class="px-4 py-2 text-slate-600">{{ formatDate(bar.date) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-slate-600">{{ formatNum(bar.open) }}</td>
                                <td class="hidden px-4 py-2 text-right tabular-nums text-slate-600 sm:table-cell">{{ formatNum(bar.high) }}</td>
                                <td class="hidden px-4 py-2 text-right tabular-nums text-slate-600 sm:table-cell">{{ formatNum(bar.low) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-medium text-slate-800">{{ formatNum(bar.close) }}</td>
                                <td class="hidden px-4 py-2 text-right tabular-nums text-slate-500 md:table-cell">{{ formatLarge(bar.volume) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Back link -->
            <div>
                <Link :href="route('rankings.index')" class="text-sm text-indigo-600 hover:text-indigo-500">
                    ← Torna al ranking
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
