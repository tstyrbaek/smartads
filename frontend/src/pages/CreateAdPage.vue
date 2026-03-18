<template>
  <div class="space-y-6">
    <div>
      <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 2l1.2 3.7c.3 1 1.1 1.8 2.1 2.1L19 9l-3.7 1.2c-1 .3-1.8 1.1-2.1 2.1L12 16l-1.2-3.7c-.3-1-1.1-1.8-2.1-2.1L5 9l3.7-1.2c1-.3 1.8-1.1 2.1-2.1L12 2z"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linejoin="round"
          />
          <path
            d="M19 14l.7 2.1c.2.6.6 1.1 1.2 1.2L23 18l-2.1.7c-.6.2-1.1.6-1.2 1.2L19 22l-.7-2.1c-.2-.6-.6-1.1-1.2-1.2L15 18l2.1-.7c.6-.2 1.1-.6 1.2-1.2L19 14z"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linejoin="round"
          />
        </svg>
        <h1 class="text-xl font-semibold">Opret annonce</h1>
      </div>
      <p class="mt-1 text-sm text-gray-600">Upload billeder og skriv en tekst for at generere en ny annonce.</p>
    </div>

    <div class="space-y-4">
      <div class="flex items-center justify-end gap-3">
        <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M14 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <path d="M10 9H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <path d="M10 13H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <path d="M20 4v6h-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M20 10a8 8 0 0 1-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
        <div class="text-sm font-medium text-gray-900">Annonce størrelse:</div>
        <label class="sr-only" for="sizePreset">Format</label>
        <select id="sizePreset" v-model="sizePreset" class="h-9 rounded-lg border bg-white px-3 text-sm">
          <option v-for="opt in sizePresetOptions" :key="opt.value" :value="opt.value">{{ opt.label }} px</option>
        </select>
      </div>

      <div class="rounded-xl border bg-white">
        <div class="flex items-center justify-between gap-3 px-4 py-3">
          <div class="text-sm font-medium text-gray-900">Annonce tekst</div>
          <div class="flex items-center gap-2">
            <SpeechToTextButton v-model="text" @error="onSpeechError" />
          </div>
        </div>
        <div class="border-t px-4 py-4">
          <textarea id="text" v-model="text" class="min-h-[14rem] w-full rounded border bg-gray-50 px-3 py-2"></textarea>
          <p class="mt-2 text-xs text-gray-600">
            Skriv den tekst der skal stå på annoncen. AI'en må ikke ændre teksten, så tjek stavning og tegnsætning.
          </p>
        </div>
      </div>

      <details class="group rounded-xl border bg-white">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 select-none">
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 7h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              <path d="M4 12h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              <path d="M4 17h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
            <div class="text-sm font-medium text-gray-900">Billeder ({{ selectedImageItems.length }}/5)</div>
          </div>
          <svg class="h-5 w-5 text-gray-500 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </summary>
        <div class="border-t px-4 py-4">
          <input
            id="images"
            class="sr-only"
            type="file"
            accept=".png,.jpg,.jpeg,.webp"
            multiple
            @change="onImages"
          />
          <label
            for="images"
            class="flex min-h-[9.5rem] w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed bg-gray-50 px-4 text-center hover:bg-gray-100"
          >
            <div class="grid justify-items-center gap-2">
              <svg class="h-8 w-8 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 16V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                <path d="M9 11l3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M20 16.5a4.5 4.5 0 0 0-4.5-4.5h-1.1A6 6 0 1 0 6 18h10.5A3.5 3.5 0 0 0 20 16.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <div class="text-sm font-medium text-gray-700">Upload billeder</div>
              <div class="text-xs text-gray-600">Klik for at vælge eller træk og slip</div>
            </div>
          </label>

          <p class="mt-2 text-xs text-gray-600">Upload billeder der skal bruges i annoncen.</p>

          <div v-if="selectedImageItems.length" class="mt-4 grid gap-2">
            <div class="text-xs text-gray-600">Træk og slip for at ændre rækkefølgen (1 = primær).</div>
            <div class="grid grid-cols-3 gap-2 md:grid-cols-6">
              <div
                v-for="(item, idx) in selectedImageItems"
                :key="item.id"
                class="relative cursor-grab overflow-hidden rounded border bg-white active:cursor-grabbing"
                draggable="true"
                @dragstart="onDragStart(item.id)"
                @dragover.prevent
                @drop="onDrop(item.id)"
              >
                <button
                  type="button"
                  class="absolute right-1 top-1 rounded bg-black/70 px-1.5 py-0.5 text-[10px] text-white"
                  @click.stop="removeImage(item.id)"
                >
                  X
                </button>
                <img :src="item.url" class="aspect-square w-full object-cover" />
                <div class="absolute left-1 top-1 rounded bg-black/70 px-1.5 py-0.5 text-[10px] text-white">{{ idx + 1 }}</div>
                <div class="p-1 text-[10px] text-gray-700">
                  <div class="truncate">{{ item.file.name }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </details>

      <details class="group rounded-xl border bg-white">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 select-none">
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M12 2l1.2 3.7c.3 1 1.1 1.8 2.1 2.1L19 9l-3.7 1.2c-1 .3-1.8 1.1-2.1 2.1L12 16l-1.2-3.7c-.3-1-1.1-1.8-2.1-2.1L5 9l3.7-1.2c1-.3 1.8-1.1 2.1-2.1L12 2z"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linejoin="round"
              />
            </svg>
            <div class="text-sm font-medium text-gray-900">Instrukser til AI (valgfrit)</div>
          </div>
          <svg class="h-5 w-5 text-gray-500 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </summary>
        <div class="border-t px-4 py-4">
          <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
              <label class="text-sm font-medium" for="instructions">Instrukser</label>
              <SpeechToTextButton v-model="instructions" @error="onSpeechError" />
            </div>
            <textarea id="instructions" v-model="instructions" class="min-h-24 w-full rounded border bg-gray-50 px-3 py-2"></textarea>
            <p class="text-xs text-gray-600">
              Beskriv hvordan annoncen skal se ud. Fx "minimalistisk", "ingen mennesker", "stort CTA", "lys baggrund".
            </p>
          </div>
        </div>
      </details>

      <details class="group rounded-xl border bg-white">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 select-none">
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7.1-7.1l-1.2 1.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 1 0 7.1 7.1l1.2-1.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="text-sm font-medium text-gray-900">Annoncelink (valgfrit)</div>
          </div>
          <svg class="h-5 w-5 text-gray-500 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </summary>
        <div class="border-t px-4 py-4">
          <div class="grid gap-2">
            <input id="targetUrl" v-model="targetUrl" class="w-full rounded border bg-gray-50 px-3 py-2" type="url" placeholder="https://..." />
            <p class="text-xs text-gray-600">Hvis angivet, bruges linket i embed i stedet for virksomhedens website.</p>
          </div>
        </div>
      </details>

      <div v-if="speechError" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        {{ speechError }}
      </div>

      <div class="space-y-3">
        <div class="flex flex-col-reverse sm:flex-row gap-3">
          <button
            class="flex w-full justify-center rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 sm:w-auto"
            :disabled="creating || !canCreateAd || !canSubmit"
            @click="onCreate"
          >
            Generér annonce
          </button>

          <div v-if="!canSubmit" class="text-xs text-gray-600">
            Udfyld annonce tekst, eller udfyld instrukser og vedhæft mindst ét referencebillede.
          </div>
        </div>

        <label class="hidden ml-2 items-center gap-2 text-sm text-gray-700">
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
      <div class="text-sm font-medium">Debug</div>
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
          >
            Download PNG
          </a>
        </div>

        <img v-if="previewUrl" :src="previewUrl" class="w-full max-w-md rounded border" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { createAd, getAd, listAllowedAdSizes, refreshTokensSummary, tokensSummary, toAbsoluteBackendUrl, type Ad, type AdCreateDebug } from '../lib/api'
import SpeechToTextButton from '../components/SpeechToTextButton.vue'

const router = useRouter()

const text = ref('')
const instructions = ref('')
const targetUrl = ref('')
const sizePreset = ref('')
const imageWidth = ref(800)
const imageHeight = ref(800)
const creating = ref(false)
const statusText = ref<string | null>(null)

const ad = ref<Ad | null>(null)
const downloadUrl = ref<string | null>(null)
const previewUrl = ref<string | null>(null)

const showDebug = ref(false)
const debugInfo = ref<AdCreateDebug | null>(null)

const speechError = ref<string | null>(null)

const minRequiredTokens = 1000

type SelectedImageItem = { id: string; file: File; url: string }

const selectedImageItems = ref<SelectedImageItem[]>([])
const dragSourceId = ref<string | null>(null)

type SizePresetOption = { value: string; label: string; width: number; height: number }

const sizePresetOptions = ref<SizePresetOption[]>([])

watch(
  sizePreset,
  (val) => {
    const preset = sizePresetOptions.value.find((x) => x.value === val)
    if (!preset) return
    imageWidth.value = preset.width
    imageHeight.value = preset.height
  },
  { immediate: true },
)

async function loadAllowedSizes() {
  const res = await listAllowedAdSizes()
  const opts = (res.sizes ?? []).map((s) => {
    const w = Number(s.width)
    const h = Number(s.height)
    return {
      value: `${w}x${h}`,
      label: `${w}×${h}`,
      width: w,
      height: h,
    }
  })
  sizePresetOptions.value = opts

  const current = String(sizePreset.value || '')
  const exists = opts.some((o) => o.value === current)
  if (!exists && opts.length > 0) {
    sizePreset.value = opts[0].value
  }
}

function onImages(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files ?? []).slice(0, 5)

  for (const item of selectedImageItems.value) {
    URL.revokeObjectURL(item.url)
  }

  selectedImageItems.value = files.map((file, idx) => ({
    id: `${Date.now()}-${idx}`,
    file,
    url: URL.createObjectURL(file),
  }))
}

function removeImage(id: string) {
  const idx = selectedImageItems.value.findIndex((x) => x.id === id)
  if (idx === -1) return

  const next = [...selectedImageItems.value]
  const [removed] = next.splice(idx, 1)
  if (removed?.url) {
    URL.revokeObjectURL(removed.url)
  }
  selectedImageItems.value = next
}

function onDragStart(id: string) {
  dragSourceId.value = id
}

function onDrop(targetId: string) {
  const sourceId = dragSourceId.value
  if (!sourceId || sourceId === targetId) return

  const items = [...selectedImageItems.value]
  const fromIndex = items.findIndex((i) => i.id === sourceId)
  const toIndex = items.findIndex((i) => i.id === targetId)
  if (fromIndex === -1 || toIndex === -1) return

  const [moved] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, moved)
  selectedImageItems.value = items
  dragSourceId.value = null
}

function onSpeechError(message: string) {
  speechError.value = message
}

const selectedImages = computed(() => selectedImageItems.value.map((x) => x.file))

const debugJson = computed(() => {
  if (!debugInfo.value) return ''
  const payload = (debugInfo.value as any).nanobananaRequest ?? debugInfo.value
  return JSON.stringify(payload, null, 2)
})

const isLoading = computed(() => creating.value || ad.value?.status === 'generating')

const canSubmit = computed(() => {
  const hasText = text.value.trim() !== ''
  const hasInstructions = instructions.value.trim() !== ''
  const hasImages = selectedImages.value.length > 0

  return hasText || (hasInstructions && hasImages)
})

const canCreateAd = computed(() => {
  const summary = tokensSummary.value
  if (!summary) return true
  if (summary.status !== 'active') return false
  return summary.remaining >= minRequiredTokens
})

let pollTimer: number | null = null

loadAllowedSizes()

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
    const res = await createAd(text.value, {
      debug: showDebug.value,
      images: selectedImages.value,
      instructions: instructions.value,
      imageWidth: imageWidth.value,
      imageHeight: imageHeight.value,
      targetUrl: targetUrl.value,
    })

    if (showDebug.value) {
      debugInfo.value = res.debug ?? null
    }

    await poll(res.adId)
    pollTimer = window.setInterval(() => {
      poll(res.adId).catch(() => {
        statusText.value = 'Kunne ikke hente status'
      })
    }, 2500)

    // Let the list page take over polling as well.
    router.replace({ path: '/ads', query: { created: res.adId } })
  } catch (e) {
    const raw = e instanceof Error ? e.message : String(e)
    if (raw.includes('insufficient_tokens')) {
      const mRemaining = raw.match(/"remaining_tokens"\s*:\s*(\d+)/)
      const mRequired = raw.match(/"required_tokens"\s*:\s*(\d+)/)
      const remaining = mRemaining ? Number(mRemaining[1]) : null
      const required = mRequired ? Number(mRequired[1]) : 1000
      statusText.value =
        remaining === null
          ? `Du har ikke nok tokens tilbage til at oprette en annonce. Der kræves mindst ${required.toLocaleString('da-DK')} tokens.`
          : `Du har ${remaining.toLocaleString('da-DK')} tokens tilbage. Der kræves mindst ${required.toLocaleString('da-DK')} tokens for at oprette en annonce.`
    } else {
      statusText.value = raw
    }
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  refreshTokensSummary().catch(() => null)
})

onBeforeUnmount(() => {
  clearPoll()

  for (const item of selectedImageItems.value) {
    URL.revokeObjectURL(item.url)
  }
})
</script>
