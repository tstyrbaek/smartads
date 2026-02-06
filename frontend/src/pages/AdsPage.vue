<template>
  <div class="space-y-6">
    <div class="md:flex md:items-start md:justify-between md:gap-4">
      <div>
        <h1 class="text-xl font-semibold">Annoncer</h1>
        <p class="mt-1 text-sm text-gray-600">Oversigt over dine annoncer og deres status.</p>
      </div>

      <div class="mt-4 md:mt-0">
        <RouterLink
          v-if="canCreateAd"
          class="flex w-full justify-center rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 md:w-auto"
          to="/ads/new"
        >
          Opret annonce
        </RouterLink>

        <div v-else class="space-y-2">
          <button
            class="flex w-full cursor-not-allowed justify-center rounded bg-green-600 px-4 py-2 text-sm font-medium text-white opacity-50 md:w-auto"
            type="button"
            disabled
          >
            Opret annonce
          </button>
        </div>
      </div>
    </div>

    <div v-if="error" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div>
      <div v-if="loading" class="px-4 py-6 text-sm text-gray-700">Henter...</div>

      <div v-else-if="ads.length === 0" class="px-4 py-6 text-sm text-gray-700">Ingen annoncer endnu.</div>

      <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        <div v-for="ad in ads" :key="ad.id" class="overflow-hidden rounded-lg border bg-white shadow-sm">
          <div class="relative">
            <div class="aspect-square w-full bg-gray-100">
              <img
                v-if="ad.localFilePath"
                :src="toAbsoluteBackendUrl(ad.localFilePath)"
                class="h-full w-full object-cover"
              />
              <div v-else class="flex h-full w-full items-center justify-center">
                <div class="text-center text-xs text-gray-500">
                  <div v-if="ad.status === 'creating' || ad.status === 'generating'" class="flex flex-col items-center gap-2">
                    <span class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-600" />
                    <span>Genererer...</span>
                  </div>
                  <span v-else-if="ad.status === 'failed'">Fejlet</span>
                  <span v-else>Intet billede</span>
                </div>
              </div>
            </div>
            <div class="absolute bottom-2 right-2 flex items-center justify-end gap-2">
              <a
                v-if="ad.status === 'success' && ad.localFilePath"
                :href="cardDownloadUrl(ad)"
                class="rounded bg-white/80 p-2 text-gray-700 hover:bg-white"
                title="Download"
                download
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
              </a>
              <button
                v-if="ad.status === 'success' && ad.localFilePath"
                class="rounded bg-white/80 p-2 text-gray-700 hover:bg-white"
                type="button"
                title="Vis"
                @click="openPreview(ad)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <button
                class="rounded bg-white/80 p-2 text-red-700 hover:bg-white disabled:opacity-50"
                type="button"
                title="Slet"
                :disabled="deletingId === ad.id"
                @click="onDelete(ad)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="previewOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closePreview"
    >
      <div class="w-full max-w-3xl rounded-lg bg-white shadow">
        <div class="flex items-center justify-between border-b px-4 py-3">
          <div class="text-sm font-medium">Annonce preview</div>
          <button class="rounded px-2 py-1 text-sm hover:bg-gray-100" type="button" @click="closePreview">
            Luk
          </button>
        </div>

        <div class="p-4">
          <img v-if="previewUrl" :src="previewUrl" class="mx-auto max-h-[70vh] w-auto rounded border" />

          <div class="mt-4 flex items-center justify-end gap-2">
            <a
              v-if="previewUrl"
              class="inline-flex rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
              :href="previewUrl"
              target="_blank"
              rel="noopener"
              download
            >
              Download
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import Pusher from 'pusher-js'
import { activeCompanyId, authToken, deleteAd, listAds, refreshTokensSummary, tokensSummary, toAbsoluteBackendUrl, type Ad } from '../lib/api'

const route = useRoute()

const ads = ref<Ad[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const previewOpen = ref(false)
const previewUrl = ref<string | null>(null)

const deletingId = ref<string | null>(null)

const minRequiredTokens = 1000
const canCreateAd = computed(() => {
  const s = tokensSummary.value
  if (!s) return true
  if (s.status !== 'active') return false
  return s.remaining >= minRequiredTokens
})

const hasGenerating = computed(() => ads.value.some((a) => a.status === 'generating' || a.status === 'creating'))

function statusDotClass(status: Ad['status']) {
  if (status === 'success') return 'bg-green-500'
  if (status === 'failed') return 'bg-red-500'
  if (status === 'generating' || status === 'creating') return 'bg-yellow-500 animate-pulse'
  return 'bg-gray-400'
}

function formatDate(iso?: string | null) {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString()
  } catch {
    return iso
  }
}

function openPreview(ad: Ad) {
  const local = ad.localFilePath
  if (!local) return
  previewUrl.value = toAbsoluteBackendUrl(local)
  previewOpen.value = true
}

function cardDownloadUrl(ad: Ad): string | undefined {
  const local = ad.localFilePath
  if (!local) return undefined
  return toAbsoluteBackendUrl(local)
}

function closePreview() {
  previewOpen.value = false
  previewUrl.value = null
}

async function onDelete(ad: Ad) {
  const ok = window.confirm('Vil du slette annoncen?')
  if (!ok) return

  deletingId.value = ad.id
  try {
    await deleteAd(ad.id)
    await load()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Kunne ikke slette annoncen'
  } finally {
    deletingId.value = null
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await listAds()
    console.log('API response for ads:', res.ads)
    ads.value = res.ads
    ensurePolling()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Kunne ikke hente annoncer'
    ads.value = []
  } finally {
    loading.value = false
  }
}

let pollTimer: number | null = null

let pusher: Pusher | null = null
let pusherChannel: Pusher.Channel | null = null
let realtimeActive = false

function teardownRealtime() {
  if (pusherChannel && pusher) {
    try {
      pusher.unsubscribe(pusherChannel.name)
    } catch {
      // ignore
    }
  }
  pusherChannel = null

  if (pusher) {
    try {
      pusher.disconnect()
    } catch {
      // ignore
    }
  }
  pusher = null
  realtimeActive = false
}

function setupRealtime() {
  const companyId = activeCompanyId.value
  const token = authToken.value
  const key = import.meta.env.VITE_PUSHER_KEY as string | undefined
  const cluster = import.meta.env.VITE_PUSHER_CLUSTER as string | undefined

  if (!companyId || !token || !key || !cluster) {
    realtimeActive = false
    return
  }

  realtimeActive = true
  clearPoll()

  pusher = new Pusher(key, {
    cluster,
    channelAuthorization: {
      endpoint: `${window.location.origin}/api/broadcasting/auth`,
      transport: 'ajax',
      headers: {
        authorization: `Bearer ${token}`,
      },
    },
  })

  const channelName = `private-company.${companyId}`
  pusherChannel = pusher.subscribe(channelName)

  pusherChannel.bind('ad.updated', (data: any) => {
    const id = String(data?.id ?? '')
    if (!id) return

    const next = [...ads.value]
    const idx = next.findIndex((a) => a.id === id)
    if (idx !== -1) {
      next[idx] = {
        ...next[idx],
        status: data?.status ?? next[idx].status,
        localFilePath: data?.localFilePath ?? next[idx].localFilePath,
        updatedAt: data?.updatedAt ?? next[idx].updatedAt,
      }
      ads.value = next
    } else {
      load().catch(() => {
        // ignore
      })
    }
  })
}

function clearPoll() {
  if (pollTimer) {
    window.clearInterval(pollTimer)
    pollTimer = null
  }
}

function ensurePolling() {
  clearPoll()
  if (realtimeActive) return
  if (!hasGenerating.value) return

  pollTimer = window.setInterval(() => {
    load().catch(() => {
      // ignore polling errors
    })
  }, 10000)
}

onMounted(async () => {
  await load()

  setupRealtime()

  if (route.query.created) {
    // no-op for now; placeholder so we can highlight later if needed
  }
})

onBeforeUnmount(() => {
  clearPoll()
  teardownRealtime()
})

onMounted(() => {
  load()
  refreshTokensSummary().catch(() => null)
})
</script>
