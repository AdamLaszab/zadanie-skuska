<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    user: {
        first_name: string;
        last_name: string;
        email: string;
        api_key?: string;
    };
}>();

const page = usePage();
const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
});

const success = ref(false);
const apiKey = ref('');

const regenerateKey = () => {
    router.post(
        route('api.key.regenerate'),
        {},
        {
            preserveScroll: true,
            onSuccess: (response) => {
                if (response?.props?.user?.api_key) {
                    apiKey.value = response.props.user.api_key;
                    success.value = true;
                }
            },
        },
    );
};
</script>

<template>
    <DashboardLayout>
        <div>
            <h1 class="mb-6 text-2xl font-bold text-sky-800">My Profile</h1>

            <form @submit.prevent="form.put(route('profile'))" class="space-y-4 rounded-lg border border-sky-100 bg-white p-6 shadow">
                <div>
                    <label class="mb-1 block text-sm font-medium">First Name</label>
                    <input v-model="form.first_name" type="text" class="w-full rounded border border-gray-300 p-2" />
                    <p v-if="form.errors.first_name" class="text-sm text-red-500">{{ form.errors.first_name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Last Name</label>
                    <input v-model="form.last_name" type="text" class="w-full rounded border border-gray-300 p-2" />
                    <p v-if="form.errors.last_name" class="text-sm text-red-500">{{ form.errors.last_name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <input v-model="form.email" type="email" class="w-full rounded border border-gray-300 p-2" />
                    <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                </div>

                <div class="flex justify-end pt-4">
                    <button :disabled="form.processing" type="submit" class="w-full rounded bg-sky-700 px-4 py-2 text-white hover:bg-sky-800">
                        Save Changes
                    </button>
                </div>

                <div class="mt-10 border-t pt-6">
                    <h2 class="mb-4 text-lg font-semibold text-sky-800">API Access</h2>

                    <form @submit.prevent="regenerateKey">
                        <button class="w-full rounded bg-sky-600 px-4 py-2 text-white hover:bg-sky-700">Regenerate API Key</button>
                    </form>

                    <div v-if="success" class="mt-4 rounded bg-gray-100 p-3 text-sm text-gray-800">
                        <p>
                            <strong>Your new API key: </strong><span class="font-mono break-all">{{ apiKey }}</span>
                        </p>
                        <p class="text-red-600">⚠️ Copy it now — you won't see it again.</p>
                    </div>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
