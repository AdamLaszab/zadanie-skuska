<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    method: {
        type: String,
        default: 'get',
    },
    as: String, // 'button' or undefined (for Link)
});
</script>

<template>
    <div>
        <button
            v-if="as === 'button'"
            type="submit"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
        >
            <slot />
        </button>

        <a
            v-else-if="as === 'a'"
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
        >
            <slot />
        </a>

        <Link
            v-else
            :href="href!"
            :method="method as 'get' | 'post' | 'put' | 'patch' | 'delete'"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
            role="menuitem"
        >
            <slot />
        </Link>
    </div>
</template>