<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold">Annoncer</h1>
        <p class="mt-1 text-sm text-gray-600">Oversigt over dine annoncer og deres status.</p>
      </div>

      <RouterLink
        class="inline-flex rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
        to="/ads/new"
      >
        Opret annonce
      </RouterLink>
    </div>

    <div v-if="error" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div class="rounded-lg border bg-white">
      <div class="grid grid-cols-[24px_minmax(0,1fr)_140px_60px] gap-3 border-b bg-gray-50 px-4 py-3 text-xs font-medium text-gray-600">
        <div></div>
        <div>Tekst</div>
        <div>Opdateret</div>
        <div></div>
      </div>

      <div v-if="loading" class="px-4 py-6 text-sm text-gray-700">Henter...</div>

      <div v-else-if="ads.length === 0" class="px-4 py-6 text-sm text-gray-700">Ingen annoncer endnu.</div>

      <div v-else>
        <div
          v-for="row in ads"
          :key="row.id"
          class="grid grid-cols-[24px_minmax(0,1fr)_140px_60px] gap-3 border-b px-4 py-3 text-sm"
        >
          <div class="flex items-center">
            <span
              class="inline-block h-3 w-3 rounded-full"
              :class="statusDotClass(row.status)"
              :title="row.status"
            ></span>
          </div>
          <div class="truncate">{{ row.text }}</div>
          <div class="text-xs text-gray-600">{{ formatDate(row.updatedAt) }}</div>
          <div class="flex items-center justify-end gap-2">
            <button
              v-if="row.status === 'success' && row.localFilePath"
              class="rounded p-1 text-blue-700 hover:bg-blue-50"
              type="button"
              title="Vis"
              @click="openPreview(row)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button
              class="rounded p-1 text-red-700 hover:bg-red-50 disabled:opacity-50"
              type="button"
              title="Slet"
              :disabled="deletingId === row.id"
              @click="onDelete(row)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
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

import { deleteAd, listAds, toAbsoluteBackendUrl, type Ad } from '../lib/api'

const route = useRoute()

const ads = ref<Ad[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const previewOpen = ref(false)
const previewUrl = ref<string | null>(null)

const deletingId = ref<string | null>(null)

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
  previewUrl.value = toAbsoluteBackendUrl('/storage/' + local.replace(/^\/+/, ''))
  previewOpen.value = true
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

function clearPoll() {
  if (pollTimer) {
    window.clearInterval(pollTimer)
    pollTimer = null
  }
}

function ensurePolling() {
  clearPoll()
  if (!hasGenerating.value) return

  pollTimer = window.setInterval(() => {
    load().catch(() => {
      // ignore polling errors
    })
  }, 2500)
}

onMounted(async () => {
  await load()

  if (route.query.created) {
    // no-op for now; placeholder so we can highlight later if needed
  }
})

onBeforeUnmount(() => {
  clearPoll()
})
</script>
