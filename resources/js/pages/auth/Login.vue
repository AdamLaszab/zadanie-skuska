<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  login: '',
  password: '',
})

const submit = () => {
  form.post('/login')
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-sky-100 p-4">
    <div class="w-full max-w-sm">
      <div class="bg-white shadow-xl rounded-2xl p-8 border border-blue-100">
        <div class="flex justify-center mb-6">
          <div class="h-12 w-12 rounded-full bg-sky-500 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
        </div>

        <h2 class="text-center text-xl font-semibold text-sky-700 mb-6">Sign in</h2>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="login" class="text-sm font-medium text-sky-700">Username or Email</label>
            <input
              v-model="form.login"
              id="login"
              type="text"
              class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 placeholder-sky-400 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
              required
              autofocus
            />
            <span class="text-xs text-red-500 mt-1" v-if="form.errors.login">{{ form.errors.login }}</span>
          </div>

          <div>
            <label for="password" class="text-sm font-medium text-sky-700">Password</label>
            <input
              v-model="form.password"
              id="password"
              type="password"
              class="mt-1 block w-full rounded-md border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-900 placeholder-sky-400 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
              required
            />
            <span class="text-xs text-red-500 mt-1" v-if="form.errors.password">{{ form.errors.password }}</span>
          </div>

          <button
            type="submit"
            class="w-full rounded-md bg-indigo-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-600 transition focus:outline-none focus:ring-2 focus:ring-indigo-400"
            :disabled="form.processing"
          >
            Sign in
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-sm text-sky-700">
            Don’t have an account?
            <Link href="/register" class="text-indigo-500 font-medium hover:underline">Create account</Link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
