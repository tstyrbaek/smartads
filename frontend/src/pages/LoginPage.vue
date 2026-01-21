<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <h1 class="text-xl font-semibold">Login</h1>
    <p class="mt-1 text-sm text-gray-600">Log ind for at redigere brand og annoncetekster.</p>

    <form class="mt-6 grid gap-4" @submit.prevent="onSubmit">
      <div class="grid gap-2">
        <label class="text-sm font-medium" for="email">Email</label>
        <input id="email" v-model="email" class="w-full rounded border px-3 py-2" type="email" required />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="password">Password</label>
        <input id="password" v-model="password" class="w-full rounded border px-3 py-2" type="password" required />
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
          type="submit"
          :disabled="loading"
        >
          Login
        </button>
        <div v-if="message" class="text-sm text-gray-700">{{ message }}</div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { getMe, login, setActiveCompanyId } from '../lib/api'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const message = ref<string | null>(null)

async function onSubmit() {
  loading.value = true
  message.value = null

  try {
    await login(email.value, password.value)

    const me = await getMe()
    if (me.companies.length === 1) {
      setActiveCompanyId(me.companies[0].id)
      await router.replace('/')
      return
    }

    await router.replace('/select-company')
  } catch (e) {
    message.value = e instanceof Error ? e.message : 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>
