<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold">Opret annonce</h1>
      <p class="mt-1 text-sm text-gray-600">Upload billeder og skriv en tekst for at generere en ny annonce.</p>
    </div>

    <div class="grid gap-4 rounded-lg border bg-white p-4">
      <div class="grid gap-2">
        <div class="flex items-center justify-between gap-3">
          <label class="text-sm font-medium" for="text">Annonce tekst</label>
          <SpeechToTextButton v-model="text" @error="onSpeechError" />
        </div>
        <textarea id="text" v-model="text" class="min-h-[18rem] w-full rounded border px-3 py-2"></textarea>
        <p class="text-xs text-gray-600">
          Skriv den tekst der skal stå på annoncen. AI'en må ikke ændre teksten, så tjek stavning og tegnsætning.
        </p>
      </div>

      <details class="rounded p-3">
        <summary class="cursor-pointer select-none text-sm font-medium text-gray-900">
          Instrukser til AI (valgfrit)
        </summary>
        <div class="mt-3 grid gap-2">
          <div class="flex items-center justify-between gap-3">
            <label class="text-sm font-medium" for="instructions">Instrukser</label>
            <SpeechToTextButton v-model="instructions" @error="onSpeechError" />
          </div>
          <textarea id="instructions" v-model="instructions" class="min-h-24 w-full rounded border bg-white px-3 py-2"></textarea>
          <p class="text-xs text-gray-600">
            Beskriv hvordan annoncen skal se ud. Fx "minimalistisk", "ingen mennesker", "stort CTA", "lys baggrund".
          </p>
        </div>
      </details>

      <div class="rounded border bg-gray-50 p-3">
        <div class="grid gap-2">
          <div class="text-sm font-medium text-gray-900">Annonce størrelse (px)</div>
          <div class="grid gap-1">
            <label class="sr-only" for="sizePreset">Format</label>
            <select id="sizePreset" v-model="sizePreset" class="w-full rounded border bg-white px-3 py-2">
              <option v-for="opt in sizePresetOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="text-xs text-gray-600">Størrelse: {{ imageWidth }}×{{ imageHeight }} px</div>
        </div>
      </div>

      <div v-if="speechError" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        {{ speechError }}
      </div>

      <div class="rounded border bg-gray-50 p-3">
        <div class="grid gap-2">
          <label class="text-sm font-medium" for="images">Billeder til annoncen (op til 5)</label>
          <input
            id="images"
            class="w-full"
            type="file"
            accept=".png,.jpg,.jpeg,.webp"
            multiple
            @change="onImages"
          />
          <p class="text-xs text-gray-600">
            Upload billeder der skal bruges i annoncen.
          </p>

          <div v-if="selectedImageItems.length" class="grid gap-2">
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
      </div>

      <div class="space-y-3">
        <div class="flex flex-col-reverse sm:flex-row gap-3">

          <button
            class="flex w-full justify-center rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 sm:w-auto"
            :disabled="creating || text.trim() === '' || !canCreateAd"
            @click="onCreate"
          >
            Generér annonce
          </button>
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

import { createAd, getAd, refreshTokensSummary, tokensSummary, toAbsoluteBackendUrl, type Ad, type AdCreateDebug } from '../lib/api'
import SpeechToTextButton from '../components/SpeechToTextButton.vue'

const router = useRouter()

const text = ref('')
const instructions = ref('')
const sizePreset = ref('square_800')
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

type SizePresetOption = { value: string; label: string; width: number | null; height: number | null }

const sizePresetOptions: SizePresetOption[] = [
  { value: 'square_800', label: 'Kvadrat (800×800)', width: 800, height: 800 },
  { value: 'square_1080', label: 'Kvadrat (1080×1080)', width: 1080, height: 1080 },
  { value: 'social_4_5', label: 'SoMe (1080×1350)', width: 1080, height: 1350 },
  { value: 'banner_16_9', label: 'Banner (1200×628)', width: 1200, height: 628 },
]

watch(
  sizePreset,
  (val) => {
    const preset = sizePresetOptions.find((x) => x.value === val)
    if (!preset) return
    if (preset.width && preset.height) {
      imageWidth.value = preset.width
      imageHeight.value = preset.height
    }
  },
  { immediate: true },
)

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

const canCreateAd = computed(() => {
  const summary = tokensSummary.value
  if (!summary) return true
  if (summary.status !== 'active') return false
  return summary.remaining >= minRequiredTokens
})

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
    const res = await createAd(text.value, {
      debug: showDebug.value,
      images: selectedImages.value,
      instructions: instructions.value,
      imageWidth: imageWidth.value,
      imageHeight: imageHeight.value,
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
