<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    canLogin:    { type: Boolean },
    canRegister: { type: Boolean },
})
</script>

<template>
    <Head title="Investment Intelligence — Screener azionario" />

    <div class="min-h-screen bg-white text-slate-800">

        <!-- Nav -->
        <nav class="sticky top-0 z-10 border-b border-slate-100 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 text-indigo-600" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                        <rect x="2"  y="20" width="6" height="10" rx="1" fill-opacity="0.4"/>
                        <rect x="10" y="13" width="6" height="17" rx="1" fill-opacity="0.65"/>
                        <rect x="18" y="5"  width="6" height="25" rx="1"/>
                        <rect x="26" y="15" width="6" height="15" rx="1" fill-opacity="0.5"/>
                    </svg>
                    <span class="text-sm font-semibold text-slate-900">Investment Intelligence</span>
                </div>
                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link :href="route('login')"
                        class="text-sm text-slate-600 transition-colors hover:text-slate-900">
                        Accedi
                    </Link>
                    <Link v-if="canRegister" :href="route('register')"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-500">
                        Registrati
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="bg-gradient-to-b from-slate-50 to-white px-6 py-24 text-center">
            <div class="mx-auto max-w-3xl">
                <span class="inline-block rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">
                    Screener quantitativo · Solo dati, nessun consiglio
                </span>
                <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    Analizza azioni con uno screener<br class="hidden sm:block" />
                    intelligente e trasparente
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-slate-500">
                    Scopri aziende interessanti da approfondire attraverso dati, fattori quantitativi
                    e spiegazioni semplici. Nessuna promessa di rendimento, solo analisi più leggibile.
                </p>
                <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <Link v-if="canRegister" :href="route('register')"
                        class="w-full rounded-lg bg-indigo-600 px-8 py-3 text-sm font-semibold text-white transition-colors hover:bg-indigo-500 sm:w-auto">
                        Inizia gratis
                    </Link>
                    <Link v-if="canLogin" :href="route('login')"
                        class="w-full rounded-lg border border-slate-200 px-8 py-3 text-sm font-semibold text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-800 sm:w-auto">
                        Accedi
                    </Link>
                </div>
            </div>
        </section>

        <!-- Come funziona -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-12 text-center">
                <h2 class="text-2xl font-bold text-slate-900">Come funziona</h2>
                <p class="mt-2 text-slate-500">
                    Sei fattori quantitativi combinati in un punteggio spiegabile per ciascun titolo.
                </p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Analisi fondamentali</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Qualità aziendale, crescita dei ricavi e solidità di bilancio valutati su dati storici.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Momentum di mercato</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Dinamiche di prezzo recenti per identificare titoli in fase di accelerazione o rallentamento.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50">
                        <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Rischio e volatilità</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Misure di drawdown e volatilità storica integrate nel punteggio complessivo.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50">
                        <svg class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Ranking spiegabile</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Ogni punteggio è accompagnato da una spiegazione in linguaggio semplice dei fattori determinanti.
                    </p>
                </div>

            </div>
        </section>

        <!-- Cosa non facciamo -->
        <section class="bg-slate-50 px-6 py-20">
            <div class="mx-auto max-w-3xl">
                <div class="mb-10 text-center">
                    <h2 class="text-2xl font-bold text-slate-900">Cosa non facciamo</h2>
                    <p class="mt-2 text-slate-500">
                        La trasparenza sui limiti è parte integrante del prodotto.
                    </p>
                </div>
                <ul class="space-y-4">
                    <li v-for="item in [
                        'Non forniamo consulenza finanziaria personalizzata',
                        'Non promettiamo rendimenti futuri',
                        'Non eseguiamo ordini di acquisto o vendita',
                        'Non sostituiamo un consulente finanziario abilitato',
                    ]" :key="item"
                        class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white px-5 py-4 text-sm text-slate-700">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        {{ item }}
                    </li>
                </ul>
            </div>
        </section>

        <!-- Per chi è utile -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-12 text-center">
                <h2 class="text-2xl font-bold text-slate-900">Per chi è utile</h2>
            </div>
            <div class="grid gap-6 md:grid-cols-3">

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Investitori autonomi</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Chi vuole studiare meglio le aziende e fare analisi personali prima di prendere
                        decisioni informate in autonomia.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Chi studia i dati finanziari</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Chi vuole capire bilanci e multipli con un linguaggio più diretto,
                        senza dover interpretare tabelle complesse da soli.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50">
                        <svg class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Chi monitora un portafoglio</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Chi vuole tenere sotto controllo watchlist e portafoglio con dati aggiornati
                        e un'analisi dell'esposizione settoriale.
                    </p>
                </div>

            </div>
        </section>

        <!-- CTA bottom -->
        <section class="bg-indigo-600 px-6 py-16 text-center">
            <div class="mx-auto max-w-2xl">
                <h2 class="text-2xl font-bold text-white">
                    Inizia ad analizzare azioni interessanti
                </h2>
                <p class="mt-3 text-indigo-200">
                    Gratuito. Nessun dato di pagamento richiesto.
                </p>
                <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <Link v-if="canRegister" :href="route('register')"
                        class="w-full rounded-lg bg-white px-8 py-3 text-sm font-semibold text-indigo-600 transition-colors hover:bg-indigo-50 sm:w-auto">
                        Crea un account
                    </Link>
                    <Link v-if="canLogin" :href="route('login')"
                        class="w-full rounded-lg border border-indigo-400 px-8 py-3 text-sm font-semibold text-white transition-colors hover:border-white sm:w-auto">
                        Accedi
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-100 px-6 py-10">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs leading-relaxed text-slate-400">
                    Le informazioni fornite hanno esclusivamente scopo informativo e non costituiscono consulenza
                    finanziaria personalizzata. I dati e i punteggi algoritmici non garantiscono rendimenti futuri
                    e non devono essere interpretati come raccomandazioni di investimento.
                    Consulta sempre un consulente finanziario abilitato prima di prendere decisioni di investimento.
                </p>
                <p class="mt-4 text-xs text-slate-300">© {{ new Date().getFullYear() }} Investment Intelligence</p>
            </div>
        </footer>

    </div>
</template>
