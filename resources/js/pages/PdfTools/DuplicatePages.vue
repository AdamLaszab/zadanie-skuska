<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const page = usePage();
const flashError = computed(() => page.props.flash?.error);
const flashSuccess = computed(() => page.props.flash?.success);
const globalErrors = computed(() => page.props.errors as Record<string, string> | undefined);

interface DuplicateForm {
  file: File | null;
  pages: string;
  duplicate_count: number | null;
  output_name: string;
  [key: string]: any;
}

const form = useForm<DuplicateForm>({
  file: null,
  pages: 'all',
  duplicate_count: 1,
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
  form.post(route('pdf.tool.duplicate_pages.process'), {
    onSuccess: () => {
      form.reset('pages', 'duplicate_count', 'output_name');
      if (fileInput.value) fileInput.value.value = ''; form.file = null;
    },
  });
}
</script>

<template>
  <DashboardLayout>
    <Head title="Duplicate PDF Pages" />
    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <h2 class="text-2xl font-semibold mb-6 text-center">Duplicate PDF Pages</h2>
            <!-- Flash a error messages -->
            <div v-if="flashSuccess" class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ flashSuccess }}</div>
            <div v-if="flashError" class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ flashError }}</div>
            <div v-if="globalErrors?.process_error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ globalErrors.process_error }}</div>
            <div v-if="form.hasErrors" class="mb-4 p-4 bg-red-50 text-red-700 rounded">
                <ul class="list-disc list-inside"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <div>
                <label for="file" class="block text-sm font-medium">Select PDF File</label>
                <input ref="fileInput" id="file" type="file" accept=".pdf" @change="handleFileChange" class="mt-1 block w-full file-input-style" />
              </div>
              <div>
                <label for="pages" class="block text-sm font-medium">Pages to Duplicate (e.g., 1, 3-5, all)</label>
                <input v-model="form.pages" id="pages" type="text" class="mt-1 block w-full input-style" placeholder="all" required />
              </div>
              <div>
                <label for="duplicate_count" class="block text-sm font-medium">Number of Duplicates</label>
                <input v-model.number="form.duplicate_count" id="duplicate_count" type="number" min="1" max="100" class="mt-1 block w-full input-style" placeholder="1" />
                <p class="mt-1 text-xs text-gray-500">How many extra copies of each specified page to add.</p>
              </div>
              <div>
                <button type="submit" :disabled="form.processing || !form.file || !form.pages" class="w-full submit-button-style">
                  {{ form.processing ? 'Processing...' : 'Duplicate Pages' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.file-input-style {
  color: rgb(107, 114, 128);
}
.dark .file-input-style {
  color: rgb(156, 163, 175);
}
.file-input-style::-webkit-file-upload-button {
  margin-right: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: none;
  font-size: 0.875rem;
  font-weight: 600;
  background-color: rgb(240, 249, 255);
  color: rgb(3, 105, 161);
}
.dark .file-input-style::-webkit-file-upload-button {
  background-color: rgb(3, 105, 161);
  color: rgb(240, 249, 255);
}
.file-input-style:hover::-webkit-file-upload-button {
  background-color: rgb(224, 242, 254);
}
.dark .file-input-style:hover::-webkit-file-upload-button {
  background-color: rgb(2, 132, 199);
}
.file-input-style:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 1), 0 0 0 4px rgba(14, 165, 233, 1);
}

.input-style {
  border-radius: 0.375rem;
  border: 1px solid rgb(209, 213, 219);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  font-size: 0.875rem;
  background-color: white;
  color: rgb(17, 24, 39);
}
.dark .input-style {
  border-color: rgb(75, 85, 99);
  background-color: rgb(55, 65, 81);
  color: rgb(243, 244, 246);
}
.input-style:focus {
  border-color: rgb(14, 165, 233);
  outline: none;
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.5);
}

.submit-button-style {
  display: flex;
  justify-content: center;
  padding: 0.5rem 1rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  font-size: 0.875rem;
  font-weight: 500;
  color: white;
  background-color: rgb(2, 132, 199);
}
.submit-button-style:hover {
  background-color: rgb(3, 105, 161);
}
.submit-button-style:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 1), 0 0 0 4px rgba(14, 165, 233, 1);
}
.submit-button-style:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>