<script setup lang="ts">
import { computed } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Link, usePage } from '@inertiajs/vue3'; // Pridal som Link a usePage
import type { NavItem } from '@/types'; // Predpokladám, že NavItem je v @/types/index.d.ts alebo podobne
// Ak chcete User typ, importujte ho tiež: import type { User } from '@/types';

// Navigačné položky pre DashboardLayout (napr. bočná lišta)
const navigationItems: NavItem[] = [
    { label: 'Dashboard', href: route('dashboard') }, // Používam route() helper
    // { label: 'Users', href: route('users.index') }, // Ak máte routy pre používateľov
    // { label: 'Settings', href: route('settings.show') }, // Ak máte routy pre nastavenia
    // Sem pridajte odkazy na vaše hlavné PDF nástroje, ak ich chcete aj v hlavnej navigácii
    { label: 'Merge PDF', href: route('pdf.tool.merge.show') },
    // ... ďalšie nástroje
];

const page = usePage();
// Predpokladáme, že user je dostupný cez page.props.auth.user, ako sme nastavili v index.d.ts
const user = computed(() => page.props.auth?.user);

// Definícia PDF nástrojov, ktoré sa zobrazia ako karty
interface PdfToolCard {
    id: string;
    title: string;
    description: string;
    href: string; // Routa k nástroju
    icon?: string; // Voliteľné: SVG cesta pre ikonu alebo názov komponentu ikony
}

const pdfTools: PdfToolCard[] = [
    {
        id: 'merge-pdf',
        title: 'Merge PDFs',
        description: 'Combine multiple PDF files into a single document.',
        href: route('pdf.tool.merge.show'), // Používam route() helper
        icon: `M12 4v16m8-8H4`, // Príklad jednoduchej SVG cesty pre plus/merge
    },
    {
        id: 'split-pdf',
        title: 'Split PDF',
        description: 'Extract pages or split a PDF into multiple smaller files.',
        href: '#', // Nahraďte skutočnou routou
        icon: `M19 13l-7 7-7-7m14-4l-7-7-7 7`, // Príklad SVG pre šípky/rozdelenie
    },
    // Sem pridajte ďalšie PDF nástroje ako objekty
    // {
    //     id: 'rotate-pdf',
    //     title: 'Rotate PDF',
    //     description: 'Rotate pages in your PDF document.',
    //     href: route('pdf.tool.rotate.show'), // Nahraďte skutočnou routou
    //     icon: `M15 3H9m6 18H9m0-9h6`, // Príklad SVG
    // },
];

</script>

<template>
    <DashboardLayout :items="navigationItems" :header-title="'Dashboard'">
        <Head title="Dashboard" /> 

        <div class="mb-8">  
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100"> 
                Welcome to PDF Tools
            </h1>
            <p v-if="user" class="text-lg text-gray-600 dark:text-gray-400 mt-1">
                Hello, {{ user.name }}! Ready to manage your PDFs?
            </p>
        </div>

        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-6">Available Tools</h2>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Link
                v-for="tool in pdfTools"
                :key="tool.id"
                :href="tool.href"
                class="block p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md hover:shadow-lg hover:border-sky-500 dark:hover:border-sky-500 transition-all duration-300 ease-in-out transform hover:-translate-y-1 group"
            >
                <div class="flex items-center mb-3">
                    <!-- Ikona (ak je definovaná) -->
                    <div v-if="tool.icon" class="mr-4 p-2 bg-sky-100 dark:bg-sky-700 rounded-full group-hover:bg-sky-200 dark:group-hover:bg-sky-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-600 dark:text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="tool.icon" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-sky-700 dark:text-sky-300 group-hover:text-sky-800 dark:group-hover:text-sky-200 transition-colors">
                        {{ tool.title }}
                    </h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    {{ tool.description }}
                </p>
                <div class="mt-4 text-right">
                    <span class="text-sm font-medium text-sky-600 dark:text-sky-400 group-hover:underline">
                        Open Tool →
                    </span>
                </div>
            </Link>

            <!-- Placeholder pre ďalšie nástroje alebo obsah -->
            <!--
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-md flex items-center justify-center text-gray-400 dark:text-gray-500">
                <p>More tools coming soon...</p>
            </div>
            -->
        </div>

    </DashboardLayout>
</template>