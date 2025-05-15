<script lang="ts" setup>
// No specific script logic for AuthLayout
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-sky-100 p-4">
    <div class="w-full max-w-sm">
      <div class="bg-white shadow-xl rounded-2xl p-8 border border-blue-100">
        <!-- Content Slot -->
        <slot />
      </div>
    </div>
  </div>
</template>