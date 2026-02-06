<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white fixed top-0 left-0 right-0 z-50">
      <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
        <div class="flex items-center gap-2">
          <RouterLink to="/" class="font-semibold text-2xl text-green-600">SmartAds</RouterLink>
          <div v-if="isAuthenticated && activeCompanyName" class="text-sm text-gray-600">
            <span class="text-gray-400">/</span>
            <span class="ml-2">{{ activeCompanyName }}</span>
          </div>
        </div>
        <div v-if="isAuthenticated" class="flex items-center gap-2">
          <!-- Desktop Menu -->
          <nav class="hidden md:flex gap-3 text-sm">
            <RouterLink class="rounded px-3 py-2 hover:bg-gray-100" to="/company">Forretning</RouterLink>
            <RouterLink class="rounded px-3 py-2 hover:bg-gray-100" to="/ads">Annoncer</RouterLink>
          </nav>

          <!-- User Profile Dropdown -->
          <div class="relative" ref="userMenuContainer">
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
          <!-- Mobile Menu Button -->
          <div class="md:hidden">
            <button
              class="inline-flex h-9 w-9 items-center justify-center rounded hover:bg-gray-100"
              type="button"
              @click="mobileMenuOpen = !mobileMenuOpen"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <line x1="3" x2="21" y1="6" y2="6" />
                <line x1="3" x2="21" y1="12" y2="12" />
                <line x1="3" x2="21" y1="18" y2="18" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen" class="md:hidden border-t">
        <nav class="flex flex-col p-4 text-sm" @click="mobileMenuOpen = false">
          <RouterLink class="rounded px-3 py-2 hover:bg-gray-100" to="/company">Forretning</RouterLink>
          <RouterLink class="rounded px-3 py-2 hover:bg-gray-100" to="/ads">Annoncer</RouterLink>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 pt-20">
      <div v-if="isAuthenticated" class="mb-6">
        <NoticeStack :items="globalNotices" />
      </div>

      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { activeCompanyId, authToken, getMe, logout, refreshTokensSummary, tokensSummary, type MeResponse } from './lib/api'
import NoticeStack from './components/NoticeStack.vue'
import { notices, removeNotice, replaceNoticesBySource, setNotice, type Notice } from './lib/notices'
import { getNotifications, type NotificationsResponse } from './lib/api'

const router = useRouter()
const isAuthenticated = computed(() => Boolean(authToken.value))
const userMenuOpen = ref(false)
const mobileMenuOpen = ref(false)
const userMenuContainer = ref<HTMLElement | null>(null)

const me = ref<MeResponse | null>(null)
const activeCompanyName = computed(() => {
  if (!me.value?.companies?.length || !activeCompanyId.value) return null
  return me.value.companies.find((c) => String(c.id) === activeCompanyId.value)?.name ?? null
})

async function onLogout() {
  try {
    await logout()
  } finally {
    me.value = null
    await router.push('/login')
  }
}

async function loadMe() {
  if (isAuthenticated.value) {
    try {
      me.value = await getMe()
    } catch (e) {
      console.error('Failed to load user data, logging out.', e)
      await onLogout()
    }
  } else {
    me.value = null
  }
}

watch(authToken, () => loadMe(), { immediate: true })

const minRequiredTokens = 1000
const tokensSummaryValue = computed(() => tokensSummary.value)

const globalNotices = computed(() => notices.value)

async function loadBackendNotices() {
  if (!isAuthenticated.value || !activeCompanyId.value) {
    replaceNoticesBySource('backend', [])
    return
  }

  try {
    const res: NotificationsResponse = await getNotifications(10)
    const next: Notice[] = (res.notifications ?? []).map((n) => ({
      id: `backend-${n.id}`,
      source: 'backend' as const,
      level: n.level,
      title: n.title,
      message: n.message,
      meta: { data: n.data, starts_at: n.starts_at, ends_at: n.ends_at },
    }))
    replaceNoticesBySource('backend', next)
  } catch {
    replaceNoticesBySource('backend', [])
  }
}

async function loadTokens() {
  if (!isAuthenticated.value) {
    tokensSummary.value = null
    return
  }
  if (!activeCompanyId.value) {
    tokensSummary.value = null
    return
  }

  try {
    await refreshTokensSummary()
  } catch {
    tokensSummary.value = null
  }
}

function syncTokenNotice() {
  const summary = tokensSummaryValue.value

  if (!isAuthenticated.value || !activeCompanyId.value) {
    removeNotice('tokens-insufficient')
    return
  }

  if (!summary || summary.status !== 'active') {
    setNotice({
      id: 'tokens-insufficient',
      source: 'system',
      level: 'warning',
      title: 'Tokens',
      message: `Du har ikke nok tokens til at oprette en annonce. Der kræves mindst ${minRequiredTokens.toLocaleString('da-DK')} tokens.`,
    })
    return
  }

  if (summary.remaining < minRequiredTokens) {
    setNotice({
      id: 'tokens-insufficient',
      source: 'system',
      level: 'warning',
      title: 'Tokens',
      message: `Du har ikke nok tokens til at oprette en annonce. Der kræves mindst ${minRequiredTokens.toLocaleString('da-DK')} tokens.`,
    })
    return
  }

  removeNotice('tokens-insufficient')
}

watch([authToken, activeCompanyId], () => {
  loadTokens()
  loadBackendNotices()
})

watch([tokensSummaryValue, authToken, activeCompanyId], () => {
  syncTokenNotice()
})

const handleClickOutside = (event: MouseEvent) => {
  if (userMenuContainer.value && !userMenuContainer.value.contains(event.target as Node)) {
    userMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadTokens()
  syncTokenNotice()
  loadBackendNotices()
})

let backendNoticeTimer: number | null = null

watch([authToken, activeCompanyId], () => {
  if (backendNoticeTimer) {
    window.clearInterval(backendNoticeTimer)
    backendNoticeTimer = null
  }

  if (!isAuthenticated.value || !activeCompanyId.value) {
    return
  }

  backendNoticeTimer = window.setInterval(() => {
    loadBackendNotices()
  }, 60000)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)

  if (backendNoticeTimer) {
    window.clearInterval(backendNoticeTimer)
    backendNoticeTimer = null
  }
})
</script>
