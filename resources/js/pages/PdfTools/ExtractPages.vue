<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
const page = usePage();

const flashError = computed(() => page.props.flash?.error);
const flashSuccess = computed(() => page.props.flash?.success);
const globalErrors = computed(() => page.props.errors as Record<string, string> | undefined);

interface ExtractForm {
  file: File | null;
  pages: string;
  output_name: string;
  // Pridanie index signature, aby vyhovel FormDataType
  // Umožňuje objektu mať aj ďalšie vlastnosti s reťazcovými kľúčmi,
  // čo Inertia useForm vyžaduje pre svoju internú prácu.
  [key: string]: any; // Alebo presnejší typ, ak viete, aké iné kľúče by mohli byť (napr. pre chyby)
                      // `any` je tu často postačujúce, pretože Inertia si to manažuje interne.
}

const form = useForm<ExtractForm>({
  file: null,
  pages: '',
  output_name: '',
});
const fileInput = ref<HTMLInputElement | null>(null);

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.file = target.files[0];
  } else {
    form.file = null;
  }
}

function submit() {
  if (page.props.flash) page.props.flash = {};
  form.post(route('pdf.tool.extract_pages.process'), {
    onSuccess: () => {
      form.reset('pages', 'output_name'); // File input sa čistí manuálne
      if (fileInput.value) {
        fileInput.value.value = '';
        form.file = null; // Resetujeme aj súbor vo formulári
      }
    },
  });
}
</script>

<template>
  <DashboardLayout>
    <Head title="Extract Pages from PDF" />

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <h2 class="text-2xl font-semibold mb-6 text-center">Extract Pages from PDF</h2>

            <div v-if="flashSuccess" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-400 rounded">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded">
                {{ flashError }}
            </div>
            <div v-if="globalErrors?.process_error" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded">
                {{ globalErrors.process_error }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <div>
                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Select PDF File
                </label>
                <input
                  ref="fileInput"
                  id="file"
                  type="file"
                  accept=".pdf"
                  @change="handleFileChange"
                  class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                         file:mr-4 file:py-2 file:px-4
                         file:rounded-md file:border-0
                         file:text-sm file:font-semibold
                         file:bg-sky-50 file:text-sky-700
                         dark:file:bg-sky-700 dark:file:text-sky-50
                         hover:file:bg-sky-100 dark:hover:file:bg-sky-600
                         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500"
                />
                <p v-if="form.errors.file" class="mt-1 text-xs text-red-500">
                  {{ form.errors.file }}
                </p>
              </div>

              <div>
                <label for="pages" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Pages to Extract (e.g., 1-3, 5, 7-10)
                </label>
                <input
                  v-model="form.pages"
                  id="pages"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                  placeholder="1, 3-5, 8"
                  required
                />
                <p v-if="form.errors.pages" class="mt-1 text-xs text-red-500">
                  {{ form.errors.pages }}
                </p>
              </div>

            
              <div>
                <button
                  type="submit"
                  :disabled="form.processing || !form.file || !form.pages"
                  class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 disabled:opacity-50"
                >
                  <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ form.processing ? 'Processing...' : 'Extract Pages' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>