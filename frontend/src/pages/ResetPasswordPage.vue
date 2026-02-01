<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <h1 class="text-xl font-semibold">Nulstil adgangskode</h1>
    <p class="mt-1 text-sm text-gray-600">Indtast din nye adgangskode.</p>

    <form class="mt-6 grid gap-4" @submit.prevent="onSubmit">
      <div class="grid gap-2">
        <label class="text-sm font-medium" for="password">Ny adgangskode</label>
        <input 
          id="password" 
          v-model="password" 
          class="w-full rounded border px-3 py-2" 
          type="password" 
          required 
          minlength="8"
        />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="password_confirmation">Bekræft adgangskode</label>
        <input 
          id="password_confirmation" 
          v-model="passwordConfirmation" 
          class="w-full rounded border px-3 py-2" 
          type="password" 
          required 
          minlength="8"
        />
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
          type="submit"
          :disabled="loading"
        >
          Nulstil adgangskode
        </button>
        <div v-if="message" class="text-sm" :class="messageType === 'error' ? 'text-red-600' : 'text-green-600'">
          {{ message }}
        </div>
      </div>
    </form>

    <div class="mt-4 text-center">
      <router-link 
        to="/login" 
        class="text-sm text-gray-600 hover:text-gray-800"
      >
        Tilbage til login
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const message = ref<string | null>(null)
const messageType = ref<'success' | 'error'>('success')

onMounted(() => {
  // Check if token and email are present in URL
  const token = route.query.token as string
  const email = route.query.email as string
  
  if (!token || !email) {
    message.value = 'Ugyldigt reset link. Anmod om et nyt link.'
    messageType.value = 'error'
  }
})

async function onSubmit() {
  if (password.value !== passwordConfirmation.value) {
    message.value = 'Adgangskoderne matcher ikke.'
    messageType.value = 'error'
    return
  }

  loading.value = true
  message.value = null

  try {
    const token = route.query.token as string
    const email = route.query.email as string

    const res = await fetch(`${import.meta.env.VITE_API_BASE || ''}/api/auth/reset-password`, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify({
        token,
        email,
        password: password.value,
        password_confirmation: passwordConfirmation.value
      }),
    })

    if (!res.ok) {
      const text = await res.text()
      throw new Error(text || `HTTP ${res.status}`)
    }

    message.value = 'Adgangskoden er blevet nulstillet. Du bliver viderestillet til login...'
    messageType.value = 'success'
    
    // Redirect to login after 2 seconds
    setTimeout(() => {
      router.push('/login')
    }, 2000)
  } catch (e) {
    message.value = e instanceof Error ? e.message : 'Failed to reset password'
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}
</script>
