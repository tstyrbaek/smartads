<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold">User</h1>
      <p class="mt-1 text-sm text-gray-600">Opdatér dine brugeroplysninger.</p>
    </div>

    <form class="grid gap-4 rounded-lg border bg-white p-4" @submit.prevent="onSaveProfile">
      <div class="grid gap-2">
        <label class="text-sm font-medium" for="name">Navn</label>
        <input id="name" v-model="profile.name" class="w-full rounded border px-3 py-2" required />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="email">Email</label>
        <input id="email" v-model="profile.email" type="email" class="w-full rounded border px-3 py-2" required />
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
          :disabled="savingProfile"
        >
          Gem profil
        </button>
        <div v-if="profileMessage" class="text-sm text-gray-700">{{ profileMessage }}</div>
      </div>
    </form>

    <form class="grid gap-4 rounded-lg border bg-white p-4" @submit.prevent="onSavePassword">
      <div class="text-sm font-medium">Skift password</div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="current_password">Nuværende password</label>
        <input
          id="current_password"
          v-model="password.current_password"
          type="password"
          class="w-full rounded border px-3 py-2"
          required
        />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="password">Nyt password</label>
        <input id="password" v-model="password.password" type="password" class="w-full rounded border px-3 py-2" required />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="password_confirmation">Gentag nyt password</label>
        <input
          id="password_confirmation"
          v-model="password.password_confirmation"
          type="password"
          class="w-full rounded border px-3 py-2"
          required
        />
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
          :disabled="savingPassword"
        >
          Opdatér password
        </button>
        <div v-if="passwordMessage" class="text-sm text-gray-700">{{ passwordMessage }}</div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getMe, updatePassword, updateProfile } from '../lib/api'

const profile = ref({ name: '', email: '' })
const password = ref({ current_password: '', password: '', password_confirmation: '' })

const savingProfile = ref(false)
const savingPassword = ref(false)
const profileMessage = ref<string | null>(null)
const passwordMessage = ref<string | null>(null)

async function load() {
  const me = await getMe()
  profile.value.name = me.user.name
  profile.value.email = me.user.email
}

async function onSaveProfile() {
  savingProfile.value = true
  profileMessage.value = null
  try {
    await updateProfile({ name: profile.value.name, email: profile.value.email })
    profileMessage.value = 'Gemt'
  } catch (e) {
    profileMessage.value = e instanceof Error ? e.message : 'Fejl'
  } finally {
    savingProfile.value = false
  }
}

async function onSavePassword() {
  savingPassword.value = true
  passwordMessage.value = null
  try {
    await updatePassword({
      current_password: password.value.current_password,
      password: password.value.password,
      password_confirmation: password.value.password_confirmation,
    })
    password.value.current_password = ''
    password.value.password = ''
    password.value.password_confirmation = ''
    passwordMessage.value = 'Gemt'
  } catch (e) {
    passwordMessage.value = e instanceof Error ? e.message : 'Fejl'
  } finally {
    savingPassword.value = false
  }
}

onMounted(load)
</script>
