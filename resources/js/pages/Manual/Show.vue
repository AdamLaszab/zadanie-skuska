<script setup lang="ts">
import { Head } from '@inertiajs/vue3'; // Removed Link as it's not used for the PDF
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const { manualContentHtml, currentLocale } = defineProps<{
  manualContentHtml: string;
  currentLocale: 'sk' | 'en';
}>();

// Make sure you have access to the route() helper.
// If you're using Ziggy with Inertia, it should be globally available.
// If not, you might need to pass the URL as a prop or generate it differently.
// Assuming route() is available:
const pdfExportUrl = route('manual.export.pdf');
</script>

<template>
  <DashboardLayout>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Wrapper for the button to align it to the right -->
        

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
          <div class="flex justify-end mb-4"> 
          <a
            :href="pdfExportUrl"
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
          >
            Export to PDF
          </a>
        </div>
          <div
            class="p-6 md:p-8 prose dark:prose-invert max-w-none"
            v-html="manualContentHtml"
          ></div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style>
/* Your styles remain the same */
.prose pre {
  background-color: #f5f5f5;
  padding: 1em;
  overflow-x: auto;
  border-radius: 0.375rem;
}
.dark .prose pre {
  background-color: #374151;
  color: #d1d5db;
}
.prose code {
  font-family: monospace;
  background-color: #eef1f3;
  padding: 0.125em 0.25em;
  border-radius: 0.25rem;
}
.dark .prose code {
  background-color: #4b5563;
  color: #e5e7eb;
}
</style>