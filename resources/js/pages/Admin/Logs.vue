<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { computed, ref } from 'vue';
import { format, parseISO, isValid as isDateValid } from 'date-fns';
import ConfirmationModal from '@/components/ConfirmationModal.vue'; // Ensure path is correct
import DashboardLayout from '@/layouts/DashboardLayout.vue';
// Import useToast from vue-toastification
import { useToast } from 'vue-toastification';

// Definícia typov pre props
interface User {
    id: number;
    username: string;
}

interface ActivityLog {
    id: number;
    user_id: number | null;
    user: User | null;
    action: string;
    access_method: 'frontend' | 'api';
    details: string | null;
    ip_address: string | null;
    city: string | null;
    country: string | null;
    created_at: string;
}

interface PaginatedLogs {
    current_page: number;
    data: ActivityLog[];
    first_page_url: string | null;
    from: number | null;
    last_page: number;
    last_page_url: string | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

const page = usePage(); // To access page.props after the request
const toast = useToast(); // Initialize useToast here

const props = defineProps<{
    logs: PaginatedLogs;
}>();

// State for the confirmation modal
const showClearLogsModal = ref(false);

const formattedLogs = computed(() => {
    return props.logs.data.map(log => {
        let formattedDate = 'N/A';
        if (log.created_at && typeof log.created_at === 'string') {
            try {
                const dateObj = parseISO(log.created_at);
                if (isDateValid(dateObj)) {
                    formattedDate = format(dateObj, 'dd.MM.yyyy HH:mm:ss');
                } else {
                    formattedDate = 'Invalid Date';
                }
            } catch (error) {
                formattedDate = 'Parse Error';
            }
        } else if (log.created_at) {
            formattedDate = 'Invalid Format';
        }

        let displayUsername: string = 'System/Unknown';
        if (log.user && typeof log.user.username === 'string' && log.user.username.trim() !== '') {
            displayUsername = log.user.username;
        } else if (log.user === null && log.user_id === null) {
            displayUsername = 'System';
        }
        
        return {
            ...log,
            created_at_formatted: formattedDate,
            username: displayUsername,
        };
    });
});

function openClearLogsConfirmation() {
  showClearLogsModal.value = true;
}

function handleClearLogsConfirm() {
  showClearLogsModal.value = false; // Close modal
  router.delete(route('admin.logs.clear'), {
    preserveScroll: true,
    onSuccess: (pageResponse) => { // Inertia passes the updated page object here
      // Check for the success flash message from the backend
      // The structure is page.props.flash.success based on standard Inertia flashing
      const successMessage = pageResponse.props.success as string | undefined;
      if (successMessage) {
        toast.success(successMessage);
        // Optional: if you want to prevent it from showing again on back nav
        // you might try to clear it from the *global* page props.
        // This can be tricky. It's often better to let Inertia manage it.
        // if (page.props.flash) page.props.flash.success = undefined;
      }
    },
    onError: (errorsObject) => { // errorsObject is the object of validation errors
      console.error('Error clearing logs:', errorsObject);
      // Check if backend sent a specific error flash message
      const errorMessage = page.props.error as string | undefined; // Check current page props for error
      if (errorMessage) {
        toast.error(errorMessage);
      } else if (errorsObject && Object.keys(errorsObject).length > 0) {
        // If there are validation errors, you could display them
        // For simplicity, just a generic error toast
        const firstError = Object.values(errorsObject)[0];
        toast.error(firstError || 'Failed to clear logs. An unknown error occurred.');
      } else {
        toast.error('Failed to clear logs. Please try again.');
      }
    }
  });
}

function handleClearLogsCancel() {
  showClearLogsModal.value = false;
}
</script>

<template>
    <DashboardLayout>
        <Head title="Activity Logs - Admin" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Activity Logs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- No local flash message display divs needed here; vue-toastification handles it -->

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                System Activity
                            </h3>
                            <div class="space-x-2">
                                <a
                                    :href="route('admin.logs.export')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 disabled:opacity-25 transition"
                                >
                                    Export to CSV
                                </a>
                                <button
                                    @click="openClearLogsConfirmation" 
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 disabled:opacity-25 transition"
                                >
                                    Clear All Logs
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <!-- ... your table ... -->
                             <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Access Method</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Details</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Address</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Location (City, Country)</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="formattedLogs.length === 0">
                                        <td colspan="9" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                            No activity logs found.
                                        </td>
                                    </tr>
                                    <tr v-for="log in formattedLogs" :key="log.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ log.id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ log.username }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ log.user_id !== null ? log.user_id : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ log.action }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span
                                                :class="{
                                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                                    'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100': log.access_method === 'frontend',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100': log.access_method === 'api',
                                                }"
                                            >
                                                {{ log.access_method }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-normal text-sm text-gray-500 dark:text-gray-400 max-w-xs break-words">{{ log.details }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ log.ip_address }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <template v-if="log.city || log.country">
                                                {{ log.city }}{{ log.city && log.country ? ', ' : '' }}{{ log.country }}
                                            </template>
                                            <template v-else>-</template>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ log.created_at_formatted }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="logs.links.length > 3" class="mt-6 flex justify-between items-center">
                           <div class="flex flex-wrap -mb-1">
                                <template v-for="(link, key) in logs.links" :key="key">
                                    <div
                                        v-if="link.url === null"
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                                        v-html="link.label"
                                    />
                                    <Link
                                        v-else
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                                        :class="{ 'bg-white dark:bg-gray-700': link.active }"
                                        :href="link.url"
                                        v-html="link.label"
                                        preserve-scroll
                                    />
                                </template>
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-400">
                                Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} results
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="showClearLogsModal"
            title="Confirm Clear Logs"
            message="Are you sure you want to delete all activity logs? This action cannot be undone."
            confirm-button-text="Clear All"
            @confirm="handleClearLogsConfirm"
            @cancel="handleClearLogsCancel"
        />
    </DashboardLayout>
</template>