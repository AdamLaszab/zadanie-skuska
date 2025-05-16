// src/layouts/DashboardLayout.vue
<script lang="ts" setup>
import NavMain from '@/components/created/NavMain.vue';
import type { NavItem } from '@/types';
import { route } from 'ziggy-js'; 

const defaultNavigationItems: NavItem[] = [
    { label: 'Dashboard', href: route('dashboard') },
    { label: 'Settings' , href: route('dashboard') },
    // { label: 'Extract Pages', href: route('pdf.tool.extract_pages.show') },
    // { label: 'Rotate PDF', href: route('pdf.tool.rotate.show') },
    // { label: 'Delete Pages', href: route('pdf.tool.delete_pages.show') },
    // { label: 'Encrypt PDF', href: route('pdf.tool.encrypt.show') },
    // { label: 'Decrypt PDF', href: route('pdf.tool.decrypt.show') },
    // { label: 'Overlay PDF', href: route('pdf.tool.overlay.show') },

];

</script>

<template>
  <div class="flex min-h-screen max-h-screen overflow-hidden bg-sky-50">

    <NavMain :items="defaultNavigationItems" class="h-screen flex-shrink-0" />

    <main class="flex-1 overflow-y-auto p-6 sm:p-8 md:p-10">
      <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 sm:p-8 border border-sky-100 dark:border-gray-700 min-h-full">
        <slot />
      </div>
    </main>
    
  </div>
</template>