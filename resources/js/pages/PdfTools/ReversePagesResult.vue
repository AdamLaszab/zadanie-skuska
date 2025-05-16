<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'; // Predpokladám, že tento layout používate

// Props, ktoré stránka očakáva z controlleru po spracovaní
const props = defineProps<{
    successMessage?: string; // Správa o úspechu
    errorMessage?: string;   // Prípadná chybová správa
    downloadUrl?: string;    // URL na stiahnutie spracovaného súboru
    fileName?: string;       // Názov súboru pre download link
}>();
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Reverse Pages Result" />

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100 text-center">
            <h2 class="text-2xl font-semibold mb-6">Reverse Pages Result</h2>

            <!-- Zobrazenie správy o úspechu -->
            <div v-if="props.successMessage" class="mb-4 p-4 bg-green-100 dark:bg-green-700 dark:text-green-100 text-green-700 border border-green-400 dark:border-green-600 rounded">
              {{ props.successMessage }}
            </div>

            <!-- Zobrazenie chybovej správy -->
            <div v-if="props.errorMessage" class="mb-4 p-4 bg-red-100 dark:bg-red-700 dark:text-red-100 text-red-700 border border-red-400 dark:border-red-600 rounded">
              {{ props.errorMessage }}
            </div>

            <!-- Tlačidlo/Link na stiahnutie, ak je k dispozícii -->
            <div v-if="props.downloadUrl && props.fileName" class="mt-6">
              <a
                :href="props.downloadUrl"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 dark:focus:ring-sky-400"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download
              </a>
            </div>

            <!-- Odkazy na ďalšie akcie -->
            <div class="mt-8">
              <Link
                :href="route('pdf.tool.reverse_pages.show')"
                class="text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-200 font-medium hover:underline"
              >
                Reverse another PDF
              </Link>
              <span class="mx-2 text-gray-400 dark:text-gray-500">|</span>
              <Link
                :href="route('dashboard')"
                class="text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-200 font-medium hover:underline"
              >
                Back to Dashboard
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>