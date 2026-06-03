<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
})
</script>

<template>
    <Head title="Investment Intelligence" />

    <div class="min-h-screen bg-slate-900 text-white">
        <!-- Nav -->
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-3">
                <svg class="h-7 w-7 text-indigo-400" viewBox="0 0 32 32" fill="currentColor">
                    <rect x="2" y="20" width="6" height="10" rx="1" fill-opacity="0.4"/>
                    <rect x="10" y="13" width="6" height="17" rx="1" fill-opacity="0.65"/>
                    <rect x="18" y="5" width="6" height="25" rx="1"/>
                    <rect x="26" y="15" width="6" height="15" rx="1" fill-opacity="0.5"/>
                </svg>
                <span class="text-sm font-semibold tracking-wide text-slate-100">Investment Intelligence</span>
            </div>
            <div v-if="canLogin" class="flex items-center gap-3">
                <Link :href="route('login')" class="text-sm text-slate-300 hover:text-white transition-colors">
                    Accedi
                </Link>
                <Link v-if="canRegister" :href="route('register')"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                    Registrati
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <section class="mx-auto max-w-4xl px-6 py-24 text-center">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-indigo-400">Stock Screener</p>
            <h1 class="text-4xl font-bold leading-tight text-white sm:text-5xl">
                Analisi quantitativa<br/>dei mercati azionari
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-400 leading-relaxed">
                Uno strumento di screening algoritmico basato su sei fattori fondamentali.
                Esplora i dati, non le opinioni.
            </p>
            <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <Link :href="route('login')"
                    class="w-full rounded-lg bg-indigo-600 px-8 py-3 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors sm:w-auto">
                    Accedi alla piattaforma
                </Link>
                <Link v-if="canRegister" :href="route('register')"
                    class="w-full rounded-lg border border-slate-700 px-8 py-3 text-sm font-semibold text-slate-300 hover:border-slate-500 hover:text-white transition-colors sm:w-auto">
                    Crea un account
                </Link>
            </div>
        </section>

        <!-- Factors -->
        <section class="mx-auto max-w-5xl px-6 pb-24">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div v-for="f in ['Qualità', 'Valore', 'Crescita', 'Momentum', 'Solidità', 'Rischio']" :key="f"
                    class="rounded-lg border border-slate-800 bg-slate-800/50 p-4 text-center">
                    <p class="text-sm font-medium text-slate-300">{{ f }}</p>
                </div>
            </div>
        </section>

        <!-- Disclaimer -->
        <footer class="border-t border-slate-800 px-6 py-8 text-center">
            <p class="mx-auto max-w-2xl text-xs text-slate-500 leading-relaxed">
                Le informazioni fornite dalla piattaforma hanno esclusivamente scopo informativo e non costituiscono
                consulenza finanziaria. I dati algoritmici non garantiscono rendimenti futuri.
            </p>
        </footer>
    </div>
</template>
