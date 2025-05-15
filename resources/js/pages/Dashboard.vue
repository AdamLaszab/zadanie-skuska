<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type { NavItem } from '@/types';

const navigationItems: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Users', href: '/users' },
    { label: 'Settings', href: '/settings' },
    // Add more items as needed
];

// You can define other props if your backend sends them
defineProps<{
    user?: { name: string }; // Example: if you pass user data
}>();
</script>

<template>
    <DashboardLayout :items="navigationItems">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-sky-800">Welcome to your Dashboard</h1>
            <p v-if="user" class="text-sky-600">Hello, {{ user.name }}!</p>
        </div>

        <!-- tu budeme asi davat jednotlive okna na tie pdf funkcia -->
        <!-- mozno by som vytvoril nejaky component osobitny do ktoreho to budeme davat a sem ho len renderneme -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-sky-200 bg-sky-100 p-6 shadow">
                <h2 class="mb-2 text-xl font-medium text-sky-700">Analytics Card</h2>
                <p class="text-sky-600">Some interesting data points here.</p>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-100 p-6 shadow">
                <h2 class="mb-2 text-xl font-medium text-sky-700">Recent Activity</h2>
                <p class="text-sky-600">List of recent activities.</p>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-100 p-6 shadow">
                <h2 class="mb-2 text-xl font-medium text-sky-700">Quick Links</h2>
                <ul class="space-y-1">
                    <li><a href="#" class="text-indigo-600 hover:underline">Create New Post</a></li>
                    <li><a href="#" class="text-indigo-600 hover:underline">Manage Comments</a></li>
                </ul>
            </div>
        </div>
    </DashboardLayout>
</template>
