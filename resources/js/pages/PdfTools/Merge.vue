<script setup lang="ts">
import { ref, computed } from 'vue'; // Pridal som computed
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';


// Nie je potrebné importovať PageProps, ak je správne augmentovaný v globálnom .d.ts
// Ak nie, museli by ste: import type { PageProps } from '@/types'; (alebo vaša cesta)
const page = usePage(); // Používame usePage
type FormErrorKeys = keyof typeof form.errors;
// Props špecifické pre túto stránku (ak nejaké sú, napr. predvyplnené dáta)
// Ak props.errors (z `back()->withErrors()`) prichádza ako Inertia prop pre stránku,
// tak je to v poriadku. Inak chyby z useForm sú v `form.errors`.
const props = defineProps<{
    // Ak vaša controller metóda showMergeForm() posiela nejaké špeciálne props
    // napr. availableOutputNames?: string[];
}>();

// Prístup k flash správam a globálnym chybám cez computed properties z usePage
const flashError = computed(() => page.props.flash?.error);
const flashSuccess = computed(() => page.props.flash?.success);
const globalErrors = computed(() => page.props.errors); // Globálne chyby (nie z useForm)

const form = useForm({
  files: [] as File[],
  output_name: '',
});

const fileInput = ref<HTMLInputElement | null>(null);

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    form.files = Array.from(target.files);
  }
}

function submit() {
  // Vymažeme predchádzajúce flash správy, ak nejaké sú, pred odoslaním
  // (voliteľné, závisí od UX)
  if (page.props.flash) {
    page.props.flash = {};
  }

  form.post(route('pdf.tool.merge.process'), {
    // onError sa postará o `form.errors`
    // Globálne chyby (ako process_error z back()->withErrors(['process_error' => ...]))
    // by sa mali objaviť v `page.props.errors` alebo ako flash správa.
    onSuccess: () => {
      form.reset();
      if (fileInput.value) {
        fileInput.value.value = '';
      }
      // Úspešné správy by mali prísť ako flash správy
      // alebo budete presmerovaní na inú stránku (MergeResult.vue)
    },
    // Môžete pridať aj onFinish, onStart atď. podľa potreby
  });
}
</script>

<template>
  <DashboardLayout>
    <Head title="Merge PDFs" />

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <h2 class="text-2xl font-semibold mb-6 text-center">Merge PDF Files</h2>

            <!-- Zobrazenie flash správ alebo globálnych chýb -->
            <div v-if="flashSuccess" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-400 rounded">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded">
                {{ flashError }}
            </div>
            <!-- Zobrazenie globálnej chyby, ak `process_error` príde cez `page.props.errors` -->
            <div v-if="globalErrors?.process_error" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded">
                {{ globalErrors.process_error }}
            </div>


            <form @submit.prevent="submit" class="space-y-6">
              <div>
                <label for="files" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Select PDF Files (min 2)
                </label>
                <input
                  ref="fileInput"
                  id="files"
                  type="file"
                  multiple
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
                /> <!-- Odstránil som `required` z HTML, validácia by mala byť primárne na backende a cez `form.errors` -->
                <p v-if="form.errors.files" class="mt-1 text-xs text-red-500">
                  {{ form.errors.files }}
                </p>
                <!-- Zobrazenie chýb pre jednotlivé súbory (napr. files.0, files.1) -->
                <div v-for="(errorKey) in Object.keys(form.errors).filter(key => key.startsWith('files.'))" :key="errorKey">
                    <p class="mt-1 text-xs text-red-500">{{ form.errors[errorKey as FormErrorKeys] }}</p>
                </div>
              </div>


              <div>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 disabled:opacity-50"
                >
                  <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ form.processing ? 'Processing...' : 'Merge PDFs' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>