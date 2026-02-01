<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <!-- Login Form -->
    <div v-if="!showResetForm">
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

      <div class="mt-4 text-center">
        <button 
          type="button" 
          @click="showResetForm = true"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          Glemt din adgangskode?
        </button>
      </div>
    </div>

    <!-- Reset Password Form -->
    <div v-else>
      <h1 class="text-xl font-semibold">Nulstil adgangskode</h1>
      <p class="mt-1 text-sm text-gray-600">Indtast din email for at modtage et link til at nulstille din adgangskode.</p>

      <form class="mt-6 grid gap-4" @submit.prevent="onResetPassword">
        <div class="grid gap-2">
          <label class="text-sm font-medium" for="reset-email">Email</label>
          <input 
            id="reset-email" 
            v-model="resetEmail" 
            class="w-full rounded border px-3 py-2" 
            type="email" 
            required 
          />
        </div>

        <div class="flex items-center gap-3">
          <button
            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            type="submit"
            :disabled="resetLoading"
          >
            Send reset link
          </button>
          <div v-if="resetMessage" class="text-sm text-gray-700">{{ resetMessage }}</div>
        </div>
      </form>

      <div class="mt-4 text-center">
        <button 
          type="button" 
          @click="showResetForm = false"
          class="text-sm text-gray-600 hover:text-gray-800"
        >
          Tilbage til login
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { getMe, login, setActiveCompanyId, forgotPassword } from '../lib/api'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const message = ref<string | null>(null)

// Reset password form state
const showResetForm = ref(false)
const resetEmail = ref('')
const resetLoading = ref(false)
const resetMessage = ref<string | null>(null)

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

async function onResetPassword() {
  resetLoading.value = true
  resetMessage.value = null

  try {
    await forgotPassword(resetEmail.value)
    
    resetMessage.value = 'Hvis emailen findes i vores system, vil du modtage et reset link.'
    resetEmail.value = ''
    
    // Switch back to login form after 3 seconds
    setTimeout(() => {
      showResetForm.value = false
      resetMessage.value = null
    }, 3000)
  } catch (e) {
    resetMessage.value = e instanceof Error ? e.message : 'Failed to send reset email'
  } finally {
    resetLoading.value = false
  }
}
</script>
