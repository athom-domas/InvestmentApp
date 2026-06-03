<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DisclaimerBox from '@/Components/DisclaimerBox.vue'
import MetricCard from '@/Components/MetricCard.vue'
import ScoreBadge from '@/Components/ScoreBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({
    modelRun: { type: Object, default: null },
    topRankings: { type: Array, default: () => [] },
    securitiesCount: { type: Number, default: 0 },
    watchlists: { type: Array, default: () => [] },
})

const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    : '—'

const sectorDistribution = computed(() => {
    const counts = {}
    props.topRankings.forEach((r) => {
        const name = r.security?.sector?.name ?? 'N/D'
        counts[name] = (counts[name] ?? 0) + 1
    })
    return Object.entries(counts).sort((a, b) => b[1] - a[1])
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Investment Intelligence</h1>
                    <p class="mt-0.5 text-sm text-slate-500">Screener azionario quantitativo</p>
                </div>
                <Link :href="route('rankings.index')"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                    Vedi tutti i ranking →
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <DisclaimerBox />

            <!-- Metric cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <MetricCard
                    label="Ultimo aggiornamento"
                    :value="modelRun ? formatDate(modelRun.finished_at) : 'N/D'"
                    :subtitle="modelRun ? `Versione ${modelRun.model_version}` : ''"
                />
                <MetricCard
                    label="Azioni analizzate"
                    :value="securitiesCount"
                    subtitle="securities attive"
                />
                <MetricCard
                    label="Universo"
                    :value="modelRun?.universe ?? 'N/D'"
                />
                <MetricCard
                    label="Le tue watchlist"
                    :value="watchlists.length"
                    subtitle="elenchi salvati"
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Top 10 rankings -->
                <div class="lg:col-span-2 rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-700">Top 10 dallo screening</h2>
                        <Link :href="route('rankings.index')" class="text-xs text-indigo-600 hover:text-indigo-500">
                            Vedi tutti
                        </Link>
                    </div>

                    <EmptyState v-if="!modelRun" title="Nessun modello disponibile"
                        description="Esegui scoring:run per avviare il motore di scoring." />

                    <div v-else-if="topRankings.length === 0" class="px-5 py-8 text-center text-sm text-slate-400">
                        Nessun ranking disponibile.
                    </div>

                    <div v-else class="divide-y divide-slate-50">
                        <div
                            v-for="r in topRankings.slice(0, 10)"
                            :key="r.id"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors"
                        >
                            <span class="w-6 shrink-0 text-center text-xs font-bold text-slate-400 tabular-nums">
                                {{ r.rank }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <Link :href="route('securities.show', r.security_id)"
                                    class="text-sm font-semibold text-slate-800 hover:text-indigo-600">
                                    {{ r.security?.ticker }}
                                </Link>
                                <p class="truncate text-xs text-slate-400">
                                    {{ r.security?.name }} · {{ r.security?.exchange?.code }} · {{ r.security?.sector?.name }}
                                </p>
                            </div>
                            <ScoreBadge :score="r.final_score" />
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Sector distribution -->
                    <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-700">Distribuzione per settore</h2>
                            <p class="text-xs text-slate-400">Basata sulle top 10 azioni</p>
                        </div>
                        <div v-if="sectorDistribution.length" class="divide-y divide-slate-50">
                            <div
                                v-for="[sector, count] in sectorDistribution"
                                :key="sector"
                                class="flex items-center justify-between px-5 py-2.5"
                            >
                                <span class="text-sm text-slate-700">{{ sector }}</span>
                                <span class="text-xs font-medium text-slate-500">{{ count }}</span>
                            </div>
                        </div>
                        <div v-else class="px-5 py-6 text-center text-sm text-slate-400">
                            Nessun dato
                        </div>
                    </div>

                    <!-- Watchlists -->
                    <div v-if="watchlists.length" class="rounded-lg bg-white shadow-sm ring-1 ring-slate-100">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-700">Le tue watchlist</h2>
                            <Link :href="route('watchlists.index')" class="text-xs text-indigo-600 hover:text-indigo-500">
                                Vedi tutte
                            </Link>
                        </div>
                        <div class="divide-y divide-slate-50">
                            <Link
                                v-for="w in watchlists"
                                :key="w.id"
                                :href="route('watchlists.show', w.id)"
                                class="block px-5 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                            >
                                {{ w.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
