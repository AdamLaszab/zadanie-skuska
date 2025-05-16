// src/layouts/DashboardLayout.vue
<script lang="ts" setup>
import NavMain from '@/components/created/NavMain.vue';
import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const page = usePage();

const can = (perm: string): boolean => {
    return Array.isArray(page.props.auth?.permissions) && page.props.auth.permissions.includes(perm);
};

const is = (role: string): boolean => {
    return Array.isArray(page.props.auth?.roles) && page.props.auth.roles.includes(role);
};
console.log(page.props.auth);
const defaultNavigationItems: NavItem[] = [
    {
        label: 'Dashboard',
        href: route('dashboard'),
        visible: can('use-pdf-tools'),
    },
    {
        label: 'Settings',
        href: route('dashboard'),
        visible: is('user'), //
    },
    {
        label: 'User List',
        href: route('admin.users'),
        visible: can('view-users'),
    },
];
//console.log(defaultNavigationItems);
const navigationItems = defaultNavigationItems.filter((item) => item.visible !== false);
</script>

<template>
    <div class="flex max-h-screen min-h-screen overflow-hidden bg-sky-50">
        <NavMain :items="navigationItems" class="h-screen flex-shrink-0" />

        <main class="flex-1 overflow-y-auto p-6 sm:p-8 md:p-10">
            <div class="min-h-full rounded-2xl border border-sky-100 bg-white p-6 shadow-xl sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <slot />
            </div>
        </main>
    </div>
</template>
