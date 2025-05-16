<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();

const flashError = computed(() => page.props.flash?.error);
const flashSuccess = computed(() => page.props.flash?.success);

interface DecryptForm {
    file: File | null;
    password: string;
    output_name: string;
    [key: string]: any;
}

const form = useForm<DecryptForm>({
    file: null,
    password: '',
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
    form.post(route('pdf.tool.decrypt.process'), {
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}
</script>

<template>
    <DashboardLayout>
        <Head title="Decrypt PDF" />

        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center text-sm font-medium text-sky-600 transition-colors duration-150 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Back to Dashboard
                            </Link>
                        </div>

                        <h2 class="mb-4 text-center text-2xl font-semibold">Decrypt PDF</h2>

                        <div v-if="flashSuccess" class="mb-4 rounded border border-green-400 bg-green-100 p-4 text-green-700">
                            {{ flashSuccess }}
                        </div>
                        <div v-if="flashError" class="mb-4 rounded border border-red-400 bg-red-100 p-4 text-red-700">
                            {{ flashError }}
                        </div>
                        <div v-if="form.hasErrors" class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-red-700">
                            <p class="font-medium">Please correct the following errors:</p>
                            <ul class="mt-1 list-inside list-disc">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Select Encrypted PDF File
                                </label>
                                <input
                                    ref="fileInput"
                                    id="file"
                                    type="file"
                                    accept=".pdf"
                                    @change="handleFileChange"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-sky-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700 hover:file:bg-sky-100 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:outline-none dark:text-gray-400 dark:file:bg-sky-700 dark:file:text-sky-50 dark:hover:file:bg-sky-600"
                                />
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Password to Decrypt PDF
                                </label>
                                <input
                                    v-model="form.password"
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    required
                                />
                            </div>

              

                            <div>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.file || !form.password"
                                    class="flex w-full justify-center rounded-md border border-transparent bg-sky-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    {{ form.processing ? 'Processing...' : 'Decrypt PDF' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
