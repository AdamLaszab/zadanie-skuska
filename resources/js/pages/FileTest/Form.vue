<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    phpInfo: Object,
    dirInfo: Object,
    serverInfo: Object,
    result: Object,
});

const form = useForm({
    testFile: null,
});

const fileInput = ref(null);

const submit = () => {
    form.post(route('file.test.upload'), {
        onFinish: () => {
            form.reset('testFile');
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <div class="max-w-6xl mx-auto p-4">
        <Head title="File Upload Test" />

        <h1 class="text-3xl font-bold mb-4">File Upload Test</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Form Section -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Upload Test Form</h2>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="testFile" class="block text-sm font-medium text-gray-700 mb-1">Select a file:</label>
                        <input 
                            id="testFile" 
                            ref="fileInput"
                            type="file" 
                            @input="form.testFile = $event.target.files[0]"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                        <div v-if="form.errors.testFile" class="text-red-500 text-sm mt-1">{{ form.errors.testFile }}</div>
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Upload File</span>
                        </button>
                    </div>
                </form>

                <!-- Upload Result Section -->
                <div v-if="result" class="mt-8">
                    <h3 class="text-lg font-medium mb-2">Upload Result</h3>
                    <div class="border rounded-md p-4" :class="{'bg-green-50 border-green-200': result.success, 'bg-red-50 border-red-200': !result.success}">
                        <p class="text-lg font-medium" :class="{'text-green-700': result.success, 'text-red-700': !result.success}">
                            {{ result.message }}
                        </p>
                        
                        <div v-if="result.file_info" class="mt-4">
                            <h4 class="font-medium mb-1">File Information:</h4>
                            <div class="text-sm space-y-1">
                                <p>Original name: {{ result.file_info.original_name }}</p>
                                <p>Size: {{ result.file_info.size }} bytes</p>
                                <p>MIME type: {{ result.file_info.mime_type }}</p>
                                <p>Extension: {{ result.file_info.extension }}</p>
                                <p>Error code: {{ result.file_info.error_code }}</p>
                                <p>Is valid: {{ result.file_info.is_valid }}</p>
                            </div>
                        </div>
                        
                        <div v-if="result.details && Object.keys(result.details).length > 0" class="mt-4">
                            <h4 class="font-medium mb-1">Details:</h4>
                            <div class="text-sm space-y-1">
                                <p v-for="(value, key) in result.details" :key="key">
                                    {{ key.replace('_', ' ') }}: {{ value }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Environment Information Section -->
            <div class="bg-white p-6 rounded-lg shadow space-y-6">
                <div>
                    <h2 class="text-xl font-semibold mb-2">PHP Configuration</h2>
                    <div class="space-y-1 text-sm">
                        <p v-for="(value, key) in phpInfo" :key="key">
                            {{ key.replace('_', ' ') }}: <span class="font-mono">{{ value }}</span>
                        </p>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-2">Directory Information</h2>
                    <div class="space-y-1 text-sm">
                        <p v-for="(value, key) in dirInfo" :key="key">
                            {{ key.replace('_', ' ') }}: <span class="font-mono">{{ value }}</span>
                        </p>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-2">Server Information</h2>
                    <div class="space-y-1 text-sm">
                        <p v-for="(value, key) in serverInfo" :key="key">
                            {{ key.replace('_', ' ') }}: <span class="font-mono">{{ value }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>