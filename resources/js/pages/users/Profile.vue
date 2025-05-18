<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    user: {
        first_name: string;
        last_name: string;
        email: string;
        api_key?: string;
    };
}>();

const page = usePage();
const profileForm = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const activeTab = ref('profile');
const success = ref(false);
const successMessage = ref('');
const apiKey = ref('');

const fullName = computed(() => {
    return `${props.user.first_name} ${props.user.last_name}`;
});

const updateProfile = () => {
    profileForm.put(route('profile'), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Profile updated successfully!';
            success.value = true;
            setTimeout(() => {
                success.value = false;
            }, 5000);
        },
    });
};

const updatePassword = () => {
    passwordForm.put(route('profile.password'), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Password changed successfully!';
            success.value = true;
            passwordForm.reset();
            setTimeout(() => {
                success.value = false;
            }, 5000);
        },
    });
};

const regenerateKey = () => {
    router.post(
        route('api.key.regenerate'),
        {},
        {
            preserveScroll: true,
            onSuccess: (response) => {
                if (response?.props?.user?.api_key) {
                    apiKey.value = response.props.user.api_key;
                    successMessage.value = 'API key generated successfully!';
                    success.value = true;
                }
            },
        },
    );
};
</script>

<template>
    <DashboardLayout>
        <div class="max-w-4xl mx-auto">
            <div class="mb-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-black">My Profile</h1>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Account: {{ fullName }}</p>
                    <p class="text-xs text-gray-500">{{ props.user.email }}</p>
                </div>
            </div>

            <div class="mb-6 border-b border-gray-200">
                <div class="flex space-x-8">
                    <button 
                        @click="activeTab = 'profile'" 
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'profile' 
                                ? 'border-sky-500 text-sky-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Profile Information
                    </button>
                    <button 
                        @click="activeTab = 'password'" 
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'password' 
                                ? 'border-sky-500 text-sky-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Change Password
                    </button>
                    <button 
                        @click="activeTab = 'api'" 
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'api' 
                                ? 'border-sky-500 text-sky-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        API Access
                    </button>
                </div>
            </div>

            <div v-if="success" class="mb-6 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'profile'" class="rounded-lg border border-sky-100 bg-white p-6 shadow">
                <form @submit.prevent="updateProfile" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                            <input
                                v-model="profileForm.first_name"
                                type="text"
                                class="w-full rounded-md border border-gray-300 p-2 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"
                            />
                            <p v-if="profileForm.errors.first_name" class="mt-1 text-sm text-red-600">
                                {{ profileForm.errors.first_name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                            <input
                                v-model="profileForm.last_name"
                                type="text"
                                class="w-full rounded-md border border-gray-300 p-2 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"
                            />
                            <p v-if="profileForm.errors.last_name" class="mt-1 text-sm text-red-600">
                                {{ profileForm.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email Address</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            class="w-full rounded-md border border-gray-300 p-2 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"
                        />
                        <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">
                            {{ profileForm.errors.email }}
                        </p>
                    </div>

                    <div class="pt-4">
                        <button
                            :disabled="profileForm.processing"
                            type="submit"
                            class="inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <svg v-if="profileForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <div v-show="activeTab === 'password'" class="rounded-lg border border-sky-100 bg-white p-6 shadow">
                <form @submit.prevent="updatePassword" class="space-y-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Current Password</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            class="w-full rounded-md border border-gray-300 p-2 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"
                        />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">New Password</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            class="w-full rounded-md border border-gray-300 p-2 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"
                        />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="w-full rounded-md border border-gray-300 p-2 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"
                        />
                    </div>

                    <div class="pt-4">
                        <button
                            :disabled="passwordForm.processing"
                            type="submit"
                            class="inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <svg v-if="passwordForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <div v-show="activeTab === 'api'" class="rounded-lg border border-sky-100 bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-sky-800">API Access</h2>
                <p class="mb-4 text-sm text-gray-600">Generate an API key to access our services programmatically.</p>

                <form @submit.prevent="regenerateKey" class="mb-4">
                    <button 
                        type="submit" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                    >
                        Generate New API Key
                    </button>
                </form>

                <div v-if="apiKey" class="mt-6 rounded-md bg-gray-50 p-4">
                    <div class="mb-2 flex items-center">
                        <div class="h-8 w-8 flex-shrink-0 rounded-full bg-sky-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-sky-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v-1l1-1 1-1-1.243-.243A6 6 0 1118 8zm-6-4a1 1 0 10-2 0v1a1 1 0 102 0V4zm-1 9a5 5 0 100-10 5 5 0 000 10z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h3 class="ml-2 text-sm font-medium text-gray-900">Your new API key</h3>
                    </div>
                    <div class="mt-2 rounded-md bg-white p-3 border border-gray-200">
                        <p class="font-mono text-sm break-all text-gray-800">{{ apiKey }}</p>
                    </div>
                    <p class="mt-2 text-sm text-red-600 flex items-center">
                        <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Copy this key now — you won't see it again
                    </p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>