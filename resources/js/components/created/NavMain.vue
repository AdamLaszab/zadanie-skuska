<script lang="ts" setup>
import { Link } from '@inertiajs/vue3'
import { defineProps } from 'vue'
import type { NavItem } from '@/types/index' // Adjust path if your types.ts is elsewhere

defineProps<{
  items: NavItem[]
}>()
</script>

<template>
  <nav class="w-64 bg-sky-700 text-sky-100 p-6 space-y-2 shadow-lg">
    <div class="mb-8 text-center">
      <Link href="/dashboard" class="text-2xl font-bold text-white hover:text-sky-200 transition">
        PDF Alchemist
      </Link>
    </div>
    <ul>
      <li v-for="item in items" :key="item.href">
        <Link
          :href="item.href"
          class="block px-4 py-3 rounded-md hover:bg-sky-600 hover:text-white transition duration-150 ease-in-out"
          :class="{ 'bg-sky-600 text-white font-semibold': $page.url.startsWith(item.href) }"
        >
          {{ item.label }}
        </Link>
      </li>
    </ul>
    <div class="absolute bottom-6 left-6 right-6">
       <Link
          href="/logout"
          method="post"
          as="button"
          class="w-full text-left px-4 py-3 rounded-md text-sky-200 hover:bg-red-500 hover:text-white transition duration-150 ease-in-out"
        >
          Logout
        </Link>
    </div>
  </nav>
</template>

<!-- <style scoped>
/* Add any specific styles for NavMain if Tailwind isn't enough */
/* For active link, we check if the current page URL starts with the item's href.
   This handles cases like /users and /users/create both highlighting "Users".
   For exact matches only, use: $page.url === item.href
*/
</style> -->