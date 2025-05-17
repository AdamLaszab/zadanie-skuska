<script lang="ts" setup>
import { Link, usePage } from '@inertiajs/vue3';
import { defineProps, ref, onMounted, onBeforeUnmount } from 'vue';
import type { NavItem } from '@/types/index';

defineProps<{
  items: NavItem[]
}>();

const page = usePage();

// Mobile responsiveness
const isMobileMenuOpen = ref(false);
const windowWidth = ref(window.innerWidth);

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const handleResize = () => {
  windowWidth.value = window.innerWidth;
  if (windowWidth.value >= 768) {
    isMobileMenuOpen.value = false;
  }
};

onMounted(() => {
  window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize);
});
</script>

<template>
  <!-- Mobile Toggle Button (only visible on small screens) -->
  <button
    @click="toggleMobileMenu"
    class="md:hidden fixed top-4 left-4 z-30 p-2 rounded-lg bg-sky-600 text-white shadow-lg"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  </button>

  <!-- Sidebar Navigation -->
  <nav
    class="w-64 h-screen bg-gradient-to-b from-sky-700 to-sky-900 text-sky-100 shadow-lg flex flex-col transition-all duration-300 ease-in-out"
    :class="[
      windowWidth < 768 ?
        (isMobileMenuOpen ? 'translate-x-0 fixed z-20' : '-translate-x-full fixed z-20') :
        'relative translate-x-0'
    ]"
  >
    <!-- Logo and Brand Section -->
    <div class="p-6 flex flex-col items-center border-b border-sky-500/30">
      <Link href="/dashboard" class="flex items-center justify-center space-x-2 group">
        <img src="/images/potion-svgrepo-com.svg" alt="PDF Alchemist Logo" class="h-16 w-auto transition-transform duration-300 group-hover:scale-110" />
        <span class="text-xl font-bold text-white group-hover:text-sky-200 transition">
          PDF Alchemist
        </span>
      </Link>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 overflow-y-auto p-4">
      <ul class="space-y-2">
        <li v-for="item in items" :key="item.href">
          <Link
            :href="item.href"
            class="flex items-center px-4 py-3 rounded-lg hover:bg-sky-600/70 hover:text-white transition-all duration-200"
            :class="{ 'bg-sky-600 text-white font-semibold shadow-md': page.url.startsWith(item.href) }"
          >
            <!-- Default icon for navigation items -->
            <svg v-if="!item.icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>

            <!-- Render custom icon if provided -->
            <span v-if="item.icon" class="mr-3" v-html="item.icon"></span>

            <span>{{ item.label }}</span>
          </Link>
        </li>
      </ul>
    </div>

    <!-- User Profile Section -->
    <div class="p-4 border-t border-sky-500/30">
      <div class="ml-4 flex flex-col items-start justify-center gap-0 mb-4">
        <div class="flex items-center">
            <p v-if="page.props.auth && page.props.auth.user" class="text-sm font-medium text-white">{{ page.props.auth.user.username }}</p>
        
          </div> 
       
          <div>
            <p v-if="page.props.auth && page.props.auth.user" class="text-xs text-sky-300">{{ page.props.auth.user.email }}</p>
          </div>
      </div>
      

      <!-- Logout Button -->
      <Link
        href="/logout"
        method="get"
        as="button"
        class="w-full text-left px-4 py-3 rounded-lg flex items-center text-sky-200 hover:bg-red-600/80 hover:text-white transition-all duration-200"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span>Logout</span>
      </Link>
    </div>

    <!-- Mobile Close Button (only visible when menu is open on mobile) -->
    <button
      v-if="windowWidth < 768 && isMobileMenuOpen"
      @click="toggleMobileMenu"
      class="absolute top-4 right-4 p-2 rounded-full bg-sky-800 text-white"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </nav>

  <!-- Overlay when mobile menu is open -->
  <div
    v-if="windowWidth < 768 && isMobileMenuOpen"
    @click="toggleMobileMenu"
    class="fixed inset-0 bg-black/50 z-10"
  ></div>
</template>