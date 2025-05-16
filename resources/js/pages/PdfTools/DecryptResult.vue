<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
const props = defineProps<{
    successMessage?: string;
    errorMessage?: string;
    downloadUrl?: string;
    fileName?: string; // Názov dešifrovaného PDF súboru
}>();
</script>

<template>
  <DashboardLayout>
    <Head title="Decrypt PDF Result" />

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100 text-center">
            <h2 class="text-2xl font-semibold mb-6">Decrypt PDF Result</h2>

            <div v-if="props.successMessage" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-400 rounded">
              {{ props.successMessage }}
            </div>

            <div v-if="props.errorMessage" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded">
              {{ props.errorMessage }}
            </div>

            <div v-if="props.downloadUrl && props.fileName" class="mt-6">
              <a
                :href="props.downloadUrl"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500"
              >
                Download
              </a>
            </div>

            <div class="mt-8">
              <Link :href="route('pdf.tool.decrypt.show')" class="text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-200">
                Decrypt another PDF
              </Link>
              <span class="mx-2">|</span>
              <Link :href="route('dashboard')" class="text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-200">
                Back to Dashboard
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>