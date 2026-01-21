<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold">Forretning</h1>
      <p class="mt-1 text-sm text-gray-600">Udfyld firma-information og upload logo.</p>
    </div>

    <form class="grid gap-4 rounded-lg border bg-white p-4" @submit.prevent="onSubmit">
      <div class="grid gap-2">
        <label class="text-sm font-medium" for="companyName">Firmanavn</label>
        <input
          id="companyName"
          v-model="state.companyName"
          class="w-full rounded border px-3 py-2"
          required
        />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="websiteUrl">Website</label>
        <input
          id="websiteUrl"
          v-model="state.websiteUrl"
          class="w-full rounded border px-3 py-2"
          type="url"
          placeholder="https://..."
        />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="companyDescription">Firma beskrivelse</label>
        <textarea
          id="companyDescription"
          v-model="state.companyDescription"
          class="min-h-24 w-full rounded border px-3 py-2"
        />
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="audienceDescription">Målgruppe beskrivelse</label>
        <textarea
          id="audienceDescription"
          v-model="state.audienceDescription"
          class="min-h-24 w-full rounded border px-3 py-2"
        />
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
          <label class="text-sm font-medium" for="primaryColor1">Primær farve 1</label>
          <input id="primaryColor1" v-model="state.primaryColor1" class="h-10 w-full" type="color" required />
        </div>
        <div class="grid gap-2">
          <label class="text-sm font-medium" for="primaryColor2">Primær farve 2</label>
          <input id="primaryColor2" v-model="state.primaryColor2" class="h-10 w-full" type="color" required />
        </div>
        <div class="grid gap-2">
          <label class="text-sm font-medium" for="primaryColor3">Primær farve 3</label>
          <input id="primaryColor3" v-model="state.primaryColor3" class="h-10 w-full" type="color" />
        </div>
        <div class="grid gap-2">
          <label class="text-sm font-medium" for="primaryColor4">Primær farve 4</label>
          <input id="primaryColor4" v-model="state.primaryColor4" class="h-10 w-full" type="color" />
        </div>
      </div>

      <div class="grid gap-2">
        <label class="text-sm font-medium" for="logo">Logo (jpg/png/webp)</label>
        <input id="logo" class="w-full" type="file" accept=".png,.jpg,.jpeg,.svg,.webp" @change="onFile" />
      </div>

      <div class="flex items-center gap-3">
        <button
          class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
          type="submit"
          :disabled="saving"
        >
          Gem firma
        </button>
        <div v-if="message" class="text-sm text-gray-700">{{ message }}</div>
      </div>
    </form>

    <div class="rounded-lg border bg-white p-4">
      <div class="flex items-center justify-between">
        <div class="text-sm font-medium">Preview</div>
        <div class="flex gap-2">
          <div class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor1 }" />
          <div class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor2 }" />
          <div v-if="state.primaryColor3" class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor3 }" />
          <div v-if="state.primaryColor4" class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor4 }" />
        </div>
      </div>

      <div class="mt-4 grid gap-3 md:grid-cols-[120px_1fr]">
        <div class="flex items-center justify-center rounded border bg-gray-50 p-2">
          <img v-if="logoUrl" :src="logoUrl" class="max-h-24 max-w-full" />
          <div v-else class="text-xs text-gray-500">Ingen logo</div>
        </div>
        <div class="grid gap-1">
          <div class="font-semibold">{{ state.companyName }}</div>
          <div class="text-sm text-gray-700">{{ state.companyDescription }}</div>
          <div class="text-sm text-gray-700">{{ state.audienceDescription }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getBrand, saveBrand, toAbsoluteBackendUrl } from '../lib/api'

const state = ref({
  companyName: '',
  websiteUrl: '',
  companyDescription: '',
  audienceDescription: '',
  primaryColor1: '#0ea5e9',
  primaryColor2: '#111827',
  primaryColor3: '#ffffff',
  primaryColor4: '#000000',
})

const logoFile = ref<File | null>(null)
const logoUrl = ref<string | null>(null)
const saving = ref(false)
const message = ref<string | null>(null)

function onFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  logoFile.value = file
}

async function load() {
  const brand = await getBrand()
  state.value.companyName = brand.companyName ?? ''
  state.value.websiteUrl = brand.websiteUrl ?? ''
  state.value.companyDescription = brand.companyDescription ?? ''
  state.value.audienceDescription = brand.audienceDescription ?? ''
  state.value.primaryColor1 = brand.primaryColor1 ?? '#0ea5e9'
  state.value.primaryColor2 = brand.primaryColor2 ?? '#111827'
  state.value.primaryColor3 = brand.primaryColor3 ?? '#ffffff'
  state.value.primaryColor4 = brand.primaryColor4 ?? '#000000'
  logoUrl.value = brand.logoPath ? toAbsoluteBackendUrl(brand.logoPath) : null
}

async function onSubmit() {
  saving.value = true
  message.value = null
  try {
    const form = new FormData()
    form.set('companyName', state.value.companyName)
    if (state.value.websiteUrl) {
      form.set('websiteUrl', state.value.websiteUrl)
    }
    form.set('companyDescription', state.value.companyDescription)
    form.set('audienceDescription', state.value.audienceDescription)
    form.set('primaryColor1', state.value.primaryColor1)
    form.set('primaryColor2', state.value.primaryColor2)
    form.set('primaryColor3', state.value.primaryColor3)
    form.set('primaryColor4', state.value.primaryColor4)
    if (logoFile.value) {
      form.set('logo', logoFile.value)
    }

    const saved = await saveBrand(form)
    logoUrl.value = saved.logoPath ? toAbsoluteBackendUrl(saved.logoPath) : logoUrl.value
    message.value = 'Gemt'
  } catch (e) {
    message.value = e instanceof Error ? e.message : 'Fejl'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  load().catch((e) => {
    message.value = e instanceof Error ? e.message : 'Kunne ikke hente brand'
  })
})
</script>
