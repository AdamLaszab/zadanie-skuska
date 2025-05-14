<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register');
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-sky-100 p-4">
        <div class="w-full max-w-md space-y-6 rounded-xl border border-blue-100 bg-white p-8 shadow-xl">
            <h2 class="text-center text-2xl font-bold text-sky-700">Create an account</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-sky-800">First Name</label>
                    <input
                        v-model="form.first_name"
                        id="first_name"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 focus:ring-2 focus:ring-indigo-400"
                        required
                    />
                    <span class="text-xs text-red-500" v-if="form.errors.first_name">{{ form.errors.first_name }}</span>
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-sky-800">Last Name</label>
                    <input
                        v-model="form.last_name"
                        id="last_name"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 focus:ring-2 focus:ring-indigo-400"
                        required
                    />
                    <span class="text-xs text-red-500" v-if="form.errors.last_name">{{ form.errors.last_name }}</span>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-sky-800">Username</label>
                    <input
                        v-model="form.username"
                        id="username"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 focus:ring-2 focus:ring-indigo-400"
                        required
                    />
                    <span class="text-xs text-red-500" v-if="form.errors.username">{{ form.errors.username }}</span>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-sky-800">Email</label>
                    <input
                        v-model="form.email"
                        id="email"
                        type="email"
                        class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 focus:ring-2 focus:ring-indigo-400"
                        required
                    />
                    <span class="text-xs text-red-500" v-if="form.errors.email">{{ form.errors.email }}</span>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-sky-800">Password</label>
                    <input
                        v-model="form.password"
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 focus:ring-2 focus:ring-indigo-400"
                        required
                    />
                    <span class="text-xs text-red-500" v-if="form.errors.password">{{ form.errors.password }}</span>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-sky-800">Confirm Password</label>
                    <input
                        v-model="form.password_confirmation"
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 focus:ring-2 focus:ring-indigo-400"
                        required
                    />
                </div>

                <button
                    type="submit"
                    class="w-full rounded-md bg-indigo-500 px-4 py-2 text-sm font-medium text-white shadow transition hover:bg-indigo-600 focus:ring-2 focus:ring-indigo-400"
                    :disabled="form.processing"
                >
                    Register
                </button>
            </form>

            <p class="text-center text-sm text-sky-700">
                Already have an account?
                <Link href="/login" class="font-medium text-indigo-500 hover:underline">Log in</Link>
            </p>
        </div>
    </div>
</template>
