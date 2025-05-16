// src/Pages/Dashboard.vue (or your path to it)
<script setup lang="ts">
import { computed } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Uses the layout with the sidebar
import { Link, usePage, Head } from '@inertiajs/vue3';
import type { NavItem } from '@/types'; // For the pdfTools structure if you reuse NavItem type
import { route } from 'ziggy-js';

const page = usePage();
const user = computed(() => page.props.auth?.user);

interface PdfToolCard {
    id: string;
    title: string;
    description: string;
    href: string;
    icon?: string;
}

const pdfTools: PdfToolCard[] = [
    {
        id: 'merge-pdf',
        title: 'Merge PDFs',
        description: 'Combine multiple PDF files into a single document.',
        href: route('pdf.tool.merge.show'),
        icon: `M12 4v16m8-8H4`,
    },
    {
        id: 'extract-pages-pdf',
        title: 'Extract Pages',
        description: 'Select and extract specific pages into a new PDF document.',
        href: route('pdf.tool.extract_pages.show'),
        icon: `M19 13l-7 7-7-7m14-4l-7-7-7 7`,
    },
    // ... Add all your other PDF tools here as cards
    {
        id: 'rotate-pdf',
        title: 'Rotate PDF',
        description: 'Change the orientation of pages in your PDF document.',
        href: route('pdf.tool.rotate.show'),
        icon: `M15 3H9m6 18H9m0-9h6M16.033 6.033a7.5 7.5 0 100 11.934M16.033 6.033L19.5 2.5M16.033 6.033l-3.536 3.535`,
    },
    {
        id: 'delete-pages-pdf',
        title: 'Delete Pages',
        description: 'Remove specific pages from your PDF document.',
        href: route('pdf.tool.delete_pages.show'),
        icon: `M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16`,
    },
    {
        id: 'encrypt-pdf',
        title: 'Encrypt PDF',
        description: 'Protect your PDF with a password and set permissions.',
        href: route('pdf.tool.encrypt.show'),
        icon: `M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z`,
    },
    {
        id: 'decrypt-pdf',
        title: 'Decrypt PDF',
        description: 'Remove password protection from your PDF file.',
        href: route('pdf.tool.decrypt.show'),
        icon: `M18 8A6 6 0 006 8v7H4a2 2 0 00-2 2v4a2 2 0 002 2h16a2 2 0 002-2v-4a2 2 0 00-2-2h-2V8zm-6-4a4 4 0 100 8 4 4 0 000-8zM8 8V6a4 4 0 118 0v2H8z`,
    },
    {
        id: 'overlay-pdf',
        title: 'Overlay PDF',
        description: 'Add a watermark, stamp, or overlay one PDF onto another.',
        href: route('pdf.tool.overlay.show'),
        icon: `M17.657 18.657l-5-5a2 2 0 00-2.828 0l-5 5a2 2 0 002.828 2.828l5-5a2 2 0 000-2.828l5-5a2 2 0 00-2.828-2.828l-5 5M6 6h.01M6 12h.01M6 18h.01M12 6h.01M12 18h.01M18 6h.01M18 12h.01`,
    },
];

</script>

<template>
    <DashboardLayout>
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
        </div>
    </DashboardLayout>
</template>