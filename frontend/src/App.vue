<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
      <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
        <div class="flex items-center gap-2">
          <div class="font-semibold text-2xl text-blue-600">SmartAdd</div>
          <div v-if="isAuthenticated && activeCompanyName" class="text-sm text-gray-600">
            <span class="text-gray-400">/</span>
            <span class="ml-2">{{ activeCompanyName }}</span>
          </div>
        </div>
        <div v-if="isAuthenticated" class="flex items-center gap-2">
          <nav class="flex gap-3 text-sm">
            <RouterLink class="rounded px-3 py-2 hover:bg-gray-100" to="/company">Forretning</RouterLink>
            <RouterLink class="rounded px-3 py-2 hover:bg-gray-100" to="/ads">Annoncer</RouterLink>
          </nav>

          <div class="relative">
            <button
              class="ml-1 inline-flex h-9 w-9 items-center justify-center rounded hover:bg-gray-100"
              type="button"
              :aria-expanded="userMenuOpen ? 'true' : 'false'"
              aria-haspopup="menu"
              @click="userMenuOpen = !userMenuOpen"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M20 21a8 8 0 10-16 0"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <path
                  d="M12 13a5 5 0 100-10 5 5 0 000 10z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </button>

            <div
              v-if="userMenuOpen"
              class="absolute right-0 z-10 mt-2 w-56 rounded-md border bg-white p-1 text-sm shadow"
              role="menu"
              @click="userMenuOpen = false"
            >
              <div class="px-3 py-2">
                <div class="truncate font-medium">{{ me?.user.name ?? 'User' }}</div>
                <div class="truncate text-xs text-gray-600">{{ me?.user.email ?? '' }}</div>
              </div>
              <div class="my-1 border-t" />

              <RouterLink class="block rounded px-3 py-2 hover:bg-gray-100" to="/profile" role="menuitem">
                Profil
              </RouterLink>
              <RouterLink
                v-if="(me?.companies?.length ?? 0) > 1"
                class="block rounded px-3 py-2 hover:bg-gray-100"
                to="/select-company"
                role="menuitem"
              >
                Skift company
              </RouterLink>

              <button
                class="block w-full rounded px-3 py-2 text-left hover:bg-gray-100"
                type="button"
                role="menuitem"
                @click="onLogout"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { activeCompanyId, authToken, getMe, logout, type MeResponse } from './lib/api'

const isAuthenticated = computed(() => Boolean(authToken.value))

const router = useRouter()
const userMenuOpen = ref(false)
const me = ref<MeResponse | null>(null)

const activeCompanyName = computed(() => {
  const id = Number(activeCompanyId.value)
  if (!id || !me.value?.companies) return ''
  return me.value.companies.find((c) => c.id === id)?.name ?? ''
})

async function loadMe() {
  if (!isAuthenticated.value) {
    me.value = null
    return
  }

  try {
    me.value = await getMe()
  } catch {
    me.value = null
  }
}

async function onLogout() {
  try {
    await logout()
  } finally {
    router.push('/login')
  }
}

onMounted(loadMe)

watch(
  () => authToken.value,
  () => {
    userMenuOpen.value = false
    loadMe()
  },
)

watch(
  () => activeCompanyId.value,
  () => {
    loadMe()
  },
)
</script>
