<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { Link } from '@inertiajs/vue3'

const showingNavigationDropdown = ref(false)

const page = usePage()
const flash = computed(() => page.props.flash)
const flashDismissed = ref(false)
watch(flash, () => { flashDismissed.value = false })
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2 text-indigo-600">
                                <AppLogo class="h-7 w-7" />
                                <span class="hidden text-sm font-semibold text-slate-800 sm:block">
                                    Investment Intelligence
                                </span>
                            </Link>
                        </div>

                        <!-- Desktop nav links -->
                        <div class="hidden space-x-1 sm:ms-8 sm:flex sm:items-center">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('rankings.index')" :active="route().current('rankings.index')">
                                Ranking
                            </NavLink>
                            <NavLink :href="route('watchlists.index')" :active="route().current('watchlists.*')">
                                Watchlist
                            </NavLink>
                            <NavLink :href="route('portfolios.index')" :active="route().current('portfolios.*')">
                                Portafogli
                            </NavLink>
                        </div>
                    </div>

                    <!-- User dropdown -->
                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-500 transition hover:text-slate-700 focus:outline-none"
                                >
                                    {{ $page.props.auth.user.name }}
                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profilo</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Esci</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-500 focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('rankings.index')" :active="route().current('rankings.index')">Ranking</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('watchlists.index')" :active="route().current('watchlists.*')">Watchlist</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('portfolios.index')" :active="route().current('portfolios.*')">Portafogli</ResponsiveNavLink>
                </div>
                <div class="border-t border-slate-200 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-slate-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-slate-500">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profilo</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Esci</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="bg-white shadow-sm border-b border-slate-100">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Flash notification -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="flash?.success && !flashDismissed"
                class="fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 shadow-lg"
            >
                <svg class="h-4 w-4 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>{{ flash.success }}</span>
                <button @click="flashDismissed = true" class="ml-1 text-emerald-500 hover:text-emerald-700">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <main>
            <slot />
        </main>
    </div>
</template>
