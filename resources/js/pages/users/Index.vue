<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const props = defineProps<{
  users: Array<{
    id: number;
    username: string;
    email: string;
    first_name: string;
    last_name: string;
    roles: Array<{ name: string }>;
  }>;
  allRoles: string[];
}>();

const forms = props.users.reduce((acc, user) => {
  acc[user.id] = useForm({
    role: user.roles[0]?.name || '',
  });
  return acc;
}, {} as Record<number, ReturnType<typeof useForm>>);
</script>

<template>
  <DashboardLayout>
    <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-full">
      <div class="flex items-center justify-start mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Users</h1>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-sky-50 to-sky-100">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Username</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Email</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Role</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in props.users" :key="user.id" class="hover:bg-sky-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-sky-100 flex items-center justify-center text-sky-600 font-medium">
                      {{ user.first_name[0] }}{{ user.last_name[0] }}
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ user.username }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ user.email }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <select 
                    v-model="forms[user.id].role" 
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm rounded-md"
                  >
                    <option v-for="role in props.allRoles" :key="role" :value="role">{{ role }}</option>
                  </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    class="bg-sky-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors duration-150"
                    @click="forms[user.id].put(route('admin.users.updateRole', user.id))"
                    :disabled="forms[user.id].processing"
                  >
                    <span v-if="forms[user.id].processing">
                      Updating...
                    </span>
                    <span v-else>
                      Update
                    </span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>