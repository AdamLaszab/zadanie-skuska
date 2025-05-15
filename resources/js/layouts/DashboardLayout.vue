<script lang="ts" setup>
import NavMain from '@/components/created/NavMain.vue'
import { defineProps } from 'vue'
import type { NavItem } from '@/types' 
defineProps<{
  items: NavItem[] 
}>()
</script>

<template>
  <div class="flex min-h-screen bg-sky-50">
    <NavMain :items="items" />

    <main class="flex-1 p-6 sm:p-8 md:p-10">
      <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8 border border-sky-100 min-h-full">
        <slot />
      </div>
    </main>
  </div>
</template>