<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/components/ApplicationLogo.vue'; // Váš komponent pre logo
import NavLink from '@/components/NavLink.vue'; // Komponent pre navigačný odkaz
import ResponsiveNavLink from '@/components/ResponsiveNavLink.vue'; // Komponent pre responzívny navigačný odkaz
import Dropdown from '@/components/Dropdown.vue'; // Komponent pre dropdown
import DropdownLink from '@/components/DropdownLink.vue'; // Komponent pre dropdown odkaz

const page = usePage();
const showingNavigationDropdown = ref(false);
const showingSidebar = ref(false); // Pre mobilnú bočnú lištu
// Typujeme usePage
const props = defineProps<{
    title?: string; // Voliteľný titul pre <Head>
}>();
const user = computed(() => page.props.auth.user);
// Ak nemáte globálne zdieľaného usera cez $page.props.auth.user, môžete ho načítať takto:
// import { usePage } from '@inertiajs/vue3';
// const page = usePage();
// const user = computed(() => page.props.auth.user as App.Models.User | null);

const logout = () => {
    router.post(route('logout'));
};

// Príklad navigácie pre bočnú lištu a hornú lištu (môžete ich mať oddelené)
const navigation = [
    { name: 'Dashboard', href: route('dashboard'), current: route().current('dashboard') },
    // ... ďalšie nástroje
];

const userNavigation = [
    // { name: 'Your Profile', href: route('profile.edit'), method: 'get' }, // Predpoklad routy pre profil
    // { name: 'Sign out', href: route('logout'), method: 'post' },
];
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Horná Navigačná Lišta -->
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <!-- Primárna Navigácia -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <!-- Navigačné Odkazy (pre väčšie obrazovky) -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink
                                    v-for="item in navigation"
                                    :key="item.name"
                                    :href="item.href"
                                    :active="item.current"
                                >
                                    {{ item.name }}
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ user.name }} <!-- Predpoklad, že user je v $page.props.auth.user -->

                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink> -->
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger pre mobilné menu -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responzívne Mobilné Menu (v hornej lište) -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink
                            v-for="item in navigation"
                            :key="item.name + '-responsive'"
                            :href="item.href"
                            :active="item.current"
                        >
                            {{ item.name }}
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responzívne Nastavenia Používateľa -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                {{ user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <!-- <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink> -->
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Hlavný Obsah Stránky -->
            <main>
                 <!-- Voliteľná hlavička stránky -->
                <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Slot pre obsah konkrétnej stránky -->
                <div class="py-12">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                <slot />
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Voliteľná pätička -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    © {{ new Date().getFullYear() }} Your Application Name. All rights reserved.
                </p>
            </footer>
        </div>
    </div>
</template>