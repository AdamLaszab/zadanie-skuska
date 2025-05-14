<script lang="ts" setup>
import { Link, router } from '@inertiajs/vue3';

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-sky-100 text-sky-900">
        <!-- Header -->
        <header class="border-b border-blue-100 bg-white shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                <h1 class="text-lg font-semibold text-sky-700">PDF Tools App</h1>
                <nav class="space-x-4">
                    <Link href="/dashboard" class="font-medium text-sky-800 hover:text-indigo-500">Dashboard</Link>
                    <Link href="/profile" class="font-medium text-sky-800 hover:text-indigo-500">Profile</Link>
                    <button @click="logout" class="rounded-md bg-indigo-500 px-4 py-1.5 text-sm text-white transition hover:bg-indigo-600">
                        Logout
                    </button>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl p-6">
            <slot />
        </main>
    </div>
</template>
