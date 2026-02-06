<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold">Annonce</h1>
      <p class="mt-1 text-sm text-gray-600">Skriv teksten til annoncen og generér en PNG.</p>
    </div>

    <div class="grid gap-4 rounded-lg border bg-white p-4">
      <div v-if="tokensSummary" class="rounded border bg-gray-50 p-3 text-sm text-gray-800">
        <div class="flex items-center justify-between gap-3">
          <div class="font-medium">Tokens (denne måned)</div>
          <div class="text-xs text-gray-600">{{ tokensSummary.period }}</div>
        </div>
        <div class="mt-2 grid grid-cols-3 gap-3 text-sm">
          <div>
            <div class="text-xs text-gray-600">Brugt</div>
            <div class="font-semibold">{{ tokensSummary.used.toLocaleString('da-DK') }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-600">Limit</div>
            <div class="font-semibold">{{ tokensSummary.limit.toLocaleString('da-DK') }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-600">Tilbage</div>
            <div class="font-semibold">{{ tokensSummary.remaining.toLocaleString('da-DK') }}</div>
          </div>
        </div>

        <div v-if="!canCreateAd" class="mt-3 rounded bg-yellow-50 p-3 text-sm text-yellow-900">
          Du har ikke nok tokens tilbage til at oprette en annonce. Der kræves mindst {{ minRequiredTokens.toLocaleString('da-DK') }} tokens.
        </div>
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="text">Annonce tekst</label>
        <textarea id="text" v-model="text" class="min-h-28 w-full rounded border px-3 py-2" />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="images">Reference billeder (op til 3)</label>
        <input
          id="images"
          class="w-full"
          type="file"
          accept=".png,.jpg,.jpeg,.webp"
          multiple
          @change="onImages"
        />
        <div v-if="selectedImages.length" class="text-xs text-gray-600">Valgt: {{ selectedImages.length }}</div>
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
          :disabled="creating || text.trim() === '' || !canCreateAd"
          @click="onCreate"
        >
          Generér annonce
        </button>
        <label class="ml-2 inline-flex items-center gap-2 text-sm text-gray-700">
          <input v-model="showDebug" type="checkbox" class="h-4 w-4" />
          Debug prompt
        </label>
        <div v-if="isLoading" class="flex items-center gap-2 text-sm text-gray-700">
          <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" />
          <span>Genererer annonce...</span>
        </div>
        <div v-else-if="statusText" class="text-sm text-gray-700">{{ statusText }}</div>
      </div>
    </div>

    <div v-if="debugInfo" class="rounded-lg border bg-white p-4">
      <div class="text-sm font-medium">Debug (NanoBanana request JSON)</div>
      <pre class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-800">{{ debugJson }}</pre>
    </div>

    <div v-if="ad" class="rounded-lg border bg-white p-4">
      <div class="flex items-center justify-between">
        <div class="text-sm font-medium">Status</div>
        <div class="text-sm text-gray-600">{{ ad.status }}</div>
      </div>

      <div v-if="ad.status === 'failed'" class="mt-3 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        {{ ad.error || 'Fejl' }}
      </div>

      <div v-if="ad.status === 'success'" class="mt-4 space-y-3">
        <div v-if="downloadUrl" class="flex items-center gap-3">
          <a
            class="inline-flex rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
            :href="downloadUrl"
            target="_blank"
            rel="noopener"
            download
            >Download PNG</a>
        </div>

        <img v-if="previewUrl" :src="previewUrl" class="w-full max-w-md rounded border" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { createAd, getAd, getTokensSummary, toAbsoluteBackendUrl, type Ad, type AdCreateDebug, type TokensSummaryResponse } from '../lib/api'

const text = ref('')
const creating = ref(false)
const statusText = ref<string | null>(null)

const ad = ref<Ad | null>(null)
const downloadUrl = ref<string | null>(null)
const previewUrl = ref<string | null>(null)

const showDebug = ref(false)
const debugInfo = ref<AdCreateDebug | null>(null)

const selectedImages = ref<File[]>([])

const minRequiredTokens = 1000
const tokensSummary = ref<TokensSummaryResponse | null>(null)

function onImages(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files ?? []).slice(0, 3)
  selectedImages.value = files
}

const debugJson = computed(() => {
  if (!debugInfo.value) return ''

  const payload = (debugInfo.value as any).nanobananaRequest ?? debugInfo.value
  return JSON.stringify(payload, null, 2)
})

const isLoading = computed(() => creating.value || ad.value?.status === 'generating')

const canCreateAd = computed(() => {
  if (!tokensSummary.value) return true
  if (tokensSummary.value.status !== 'active') return false
  return tokensSummary.value.remaining >= minRequiredTokens
})

async function loadTokens() {
  try {
    tokensSummary.value = await getTokensSummary()
  } catch {
    tokensSummary.value = null
  }
}

let pollTimer: number | null = null

function clearPoll() {
  if (pollTimer) {
    window.clearInterval(pollTimer)
    pollTimer = null
  }
}

async function poll(adId: string) {
  const res = await getAd(adId)
  ad.value = res.ad
  downloadUrl.value = res.downloadUrl ? toAbsoluteBackendUrl(res.downloadUrl) : null
  previewUrl.value = res.previewUrl ? toAbsoluteBackendUrl(res.previewUrl) : null

  if (ad.value.status === 'success' || ad.value.status === 'failed') {
    clearPoll()
    statusText.value = null
  } else {
    statusText.value = 'Genererer...'
  }
}

async function onCreate() {
  creating.value = true
  statusText.value = null
  ad.value = null
  downloadUrl.value = null
  previewUrl.value = null
  debugInfo.value = null
  clearPoll()

  try {
    const res = await createAd(text.value, { debug: showDebug.value, images: selectedImages.value })
    statusText.value = res.status

    if (showDebug.value) {
      debugInfo.value = res.debug ?? null
    }

    await poll(res.adId)
    pollTimer = window.setInterval(() => {
      poll(res.adId).catch(() => {
        statusText.value = 'Kunne ikke hente status'
      })
    }, 2500)
  } catch (e) {
    statusText.value = e instanceof Error ? e.message : 'Fejl'
  } finally {
    creating.value = false
  }
}

onBeforeUnmount(() => {
  clearPoll()
})

onMounted(() => {
  loadTokens()
})
</script>
