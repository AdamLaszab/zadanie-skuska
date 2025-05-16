<script setup lang="ts">
    import DashboardLayout from '@/layouts/DashboardLayout.vue';

    defineProps<{
        users: {
            id: number;
            username: string;
            email: string;
            roles: { name: string }[];
            created_at: string;
        }[];
    }>();
</script>

<template>
    <DashboardLayout>
        <div>
            <h1 class="mb-6 text-3xl font-bold text-sky-800">Users</h1>

            <div class="overflow-x-auto rounded-lg border border-sky-100 bg-white shadow">
                <table class="min-w-full table-auto text-left text-sm text-gray-700">
                    <thead class="bg-sky-700 text-white">
                        <tr>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b transition hover:bg-sky-50">
                            <td class="px-4 py-3 font-medium">{{ user.username }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span
                                    v-for="(role, index) in user.roles"
                                    :key="index"
                                    class="mr-2 inline-block rounded bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800"
                                >
                                    {{ role.name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </DashboardLayout>
</template>
