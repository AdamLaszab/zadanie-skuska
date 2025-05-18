<script lang="ts" setup>
import NavMain from '@/components/created/NavMain.vue';
import type { NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const page = usePage();

const can = (perm: string): boolean => {
    const map = {
        'use-pdf-tools': page.props.canUsePdfTools,
        'view-users': page.props.canViewUsers,
        'view-own-usage-history': page.props.canViewOwnHistory,
        'view-any-usage-history': page.props.canViewAnyHistory,
        'export-any-usage-history': page.props.canExportHistory,
        'delete-any-usage-history': page.props.canDeleteHistory,
    };

    return map[perm] === true;
};

const is = (role: string): boolean => {
    const map = {
        admin: page.props.isAdmin,
        user: page.props.auth?.user?.username === 'regular', // example fallback
    };

    return map[role] === true;
};

const navItems: NavItem[] = [
    { label: 'Dashboard', href: route('dashboard'), permission: 'use-pdf-tools' },
    { label: 'Profile', href: route('profile'), permission: 'use-pdf-tools' },
    { label: 'Activity', href: route('admin.logs.index'), permission: 'view-users' },
    { label: 'Manual', href: route('manual.show'), permission: 'use-pdf-tools' },git
    { label: 'User List', href: route('admin.users'), permission: 'view-users' },
    // { label: 'Admin Panel', href: route('admin.logs.index'), role: 'admin' },
];

const navigationItems = navItems.filter((item) => {
    if (item.permission) return can(item.permission);
    if (item.role) return is(item.role);
    return true; 
});
</script>

<template>
    <div class="flex max-h-screen min-h-screen overflow-hidden bg-sky-50">
        <NavMain :items="navigationItems" class="h-screen flex-shrink-0" />

        <main class="flex-1 overflow-y-auto p-6 sm:p-8 md:p-10">
            <div class="min-h-full rounded-2xl border border-sky-100 bg-white p-6 sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                <slot />
            </div>
            <!-- <slot /> -->
        </main>
    </div>
</template>
