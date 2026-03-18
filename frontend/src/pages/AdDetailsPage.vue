<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold">Annonce</h1>
        <p class="mt-1 text-sm text-gray-600">Detaljer og informationer om annoncen.</p>
      </div>

      <RouterLink class="rounded border bg-white px-3 py-2 text-sm font-medium hover:bg-gray-50" to="/ads">
        Tilbage
      </RouterLink>
    </div>

    <div v-if="error" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div v-if="loading" class="text-sm text-gray-700">Henter...</div>

    <div v-else-if="ad" class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-lg border bg-white p-4">
        <div class="text-sm font-medium text-gray-900">Preview</div>
        <div class="mt-3">
          <img
            v-if="ad.localFilePath"
            :src="toAbsoluteBackendUrl(ad.localFilePath)"
            class="block w-full h-auto max-w-full rounded border bg-gray-50 object-contain"
          />
          <div v-else class="rounded border bg-gray-50 p-6 text-sm text-gray-700">Intet preview endnu.</div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-2">
          <div class="text-xs text-gray-600">
            <div v-if="ad.imageWidth && ad.imageHeight">Størrelse: {{ ad.imageWidth }}×{{ ad.imageHeight }} px</div>
            <div>Status: {{ ad.status }}</div>
          </div>

          <a
            v-if="ad.status === 'success' && ad.localFilePath"
            class="inline-flex rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
            :href="toAbsoluteBackendUrl(ad.localFilePath)"
            target="_blank"
            rel="noopener"
            download
          >
            Download
          </a>
        </div>
      </div>

      <div class="space-y-4">
        <div class="rounded-lg border bg-white p-4">
          <div class="text-sm font-medium text-gray-900">Grunddata</div>
          <div class="mt-3 grid gap-2 text-sm">
            <div class="grid grid-cols-3 gap-3">
              <div class="text-gray-500">ID</div>
              <div class="col-span-2 font-mono text-xs break-all">{{ ad.id }}</div>
            </div>
            <div v-if="ad.title" class="grid grid-cols-3 gap-3">
              <div class="text-gray-500">Titel</div>
              <div class="col-span-2">{{ ad.title }}</div>
            </div>
            <div v-if="ad.createdAt" class="grid grid-cols-3 gap-3">
              <div class="text-gray-500">Oprettet</div>
              <div class="col-span-2">{{ formatDateTime(ad.createdAt) }}</div>
            </div>
            <div v-if="ad.updatedAt" class="grid grid-cols-3 gap-3">
              <div class="text-gray-500">Opdateret</div>
              <div class="col-span-2">{{ formatDateTime(ad.updatedAt) }}</div>
            </div>
            <div v-if="ad.targetUrl" class="grid grid-cols-3 gap-3">
              <div class="text-gray-500">Target URL</div>
              <div class="col-span-2">
                <div v-if="!editingTargetUrl" class="flex items-center gap-2">
                  <a class="min-w-0 truncate text-blue-700 hover:underline" :href="ad.targetUrl" target="_blank" rel="noopener">{{ ad.targetUrl }}</a>
                  <button
                    class="inline-flex rounded border bg-white p-1 text-gray-700 hover:bg-gray-50"
                    type="button"
                    title="Redigér"
                    @click="startEditTargetUrl"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 20h9" />
                      <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                    </svg>
                  </button>
                </div>

                <div v-else class="space-y-2">
                  <input
                    v-model="targetUrlDraft"
                    class="w-full rounded border bg-white px-3 py-2 text-sm"
                    type="url"
                    placeholder="https://..."
                  />
                  <div class="flex items-center gap-2">
                    <button
                      class="inline-flex rounded bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                      type="button"
                      :disabled="savingTargetUrl"
                      @click="saveTargetUrl"
                    >
                      Gem
                    </button>
                    <button
                      class="inline-flex rounded border bg-white px-3 py-1.5 text-xs font-medium hover:bg-gray-50"
                      type="button"
                      :disabled="savingTargetUrl"
                      @click="cancelEditTargetUrl"
                    >
                      Annuller
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="grid grid-cols-3 gap-3">
              <div class="text-gray-500">Target URL</div>
              <div class="col-span-2">
                <div v-if="!editingTargetUrl" class="flex items-center gap-2">
                  <div class="text-sm text-gray-700">Ikke sat</div>
                  <button
                    class="inline-flex rounded border bg-white p-1 text-gray-700 hover:bg-gray-50"
                    type="button"
                    title="Tilføj"
                    @click="startEditTargetUrl"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 20h9" />
                      <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                    </svg>
                  </button>
                </div>

                <div v-else class="space-y-2">
                  <input
                    v-model="targetUrlDraft"
                    class="w-full rounded border bg-white px-3 py-2 text-sm"
                    type="url"
                    placeholder="https://..."
                  />
                  <div class="flex items-center gap-2">
                    <button
                      class="inline-flex rounded bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                      type="button"
                      :disabled="savingTargetUrl"
                      @click="saveTargetUrl"
                    >
                      Gem
                    </button>
                    <button
                      class="inline-flex rounded border bg-white px-3 py-1.5 text-xs font-medium hover:bg-gray-50"
                      type="button"
                      :disabled="savingTargetUrl"
                      @click="cancelEditTargetUrl"
                    >
                      Annuller
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="ad.error" class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ ad.error }}
          </div>
        </div>

        <div class="rounded-lg border bg-white p-4">
          <div class="text-sm font-medium text-gray-900">Tekst</div>
          <pre class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-800">{{ ad.text }}</pre>
        </div>

        <div v-if="ad.instructions" class="rounded-lg border bg-white p-4">
          <div class="text-sm font-medium text-gray-900">Instrukser</div>
          <pre class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-800">{{ ad.instructions }}</pre>
        </div>

        <div v-if="isAdmin && tokenSummary" class="rounded-lg border bg-white p-4">
          <div class="text-sm font-medium text-gray-900">Tokens</div>
          <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
            <div class="rounded border bg-gray-50 p-3">
              <div class="text-xs text-gray-600">Prompt</div>
              <div class="font-semibold">{{ tokenSummary.prompt.toLocaleString('da-DK') }}</div>
            </div>
            <div class="rounded border bg-gray-50 p-3">
              <div class="text-xs text-gray-600">Output</div>
              <div class="font-semibold">{{ tokenSummary.output.toLocaleString('da-DK') }}</div>
            </div>
            <div class="rounded border bg-gray-50 p-3">
              <div class="text-xs text-gray-600">Total</div>
              <div class="font-semibold">{{ tokenSummary.total.toLocaleString('da-DK') }}</div>
            </div>
          </div>
        </div>

        <div class="rounded-lg border bg-white p-4">
          <div class="text-sm font-medium text-gray-900">Integrationer</div>
          <div class="mt-3">
            <div v-if="(ad.integrationInstances?.length ?? 0) === 0" class="text-sm text-gray-700">Ingen integrationer.</div>
            <div v-else class="space-y-2">
              <div v-for="inst in ad.integrationInstances" :key="inst.id" class="rounded border bg-gray-50 p-3">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-gray-900">{{ inst.name }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-xs text-gray-600">
                      <div v-if="inst.published_at">Udgivet: {{ new Date(inst.published_at).toLocaleDateString('da-DK') }}</div>
                      <div v-else>Ikke udgivet</div>
                    </div>

                    <div class="mt-2 flex items-center justify-end gap-2">
                      <span
                        v-if="instanceById[inst.id]?.is_active === true"
                        class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                      >Aktiv</span>
                      <span
                        v-else
                        class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20"
                      >Inaktiv</span>

                      <button
                        class="inline-flex rounded border bg-white px-2 py-1 text-xs font-medium hover:bg-gray-50 disabled:opacity-50"
                        type="button"
                        :disabled="togglingInstanceId === inst.id || !instanceById[inst.id]"
                        @click="toggleIntegration(inst.id)"
                      >
                        {{ instanceById[inst.id]?.is_active ? 'Deaktivér' : 'Aktivér' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <details v-if="isAdmin && ad.debug" class="rounded-lg border bg-white p-4">
          <summary class="cursor-pointer select-none text-sm font-medium text-gray-900">Debug</summary>
          <pre class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-800">{{ JSON.stringify(ad.debug, null, 2) }}</pre>
        </details>

        <details v-if="isAdmin && ad.brandSnapshot" class="rounded-lg border bg-white p-4">
          <summary class="cursor-pointer select-none text-sm font-medium text-gray-900">Brand snapshot</summary>
          <pre class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-800">{{ JSON.stringify(ad.brandSnapshot, null, 2) }}</pre>
        </details>

        <details v-if="isAdmin && ad.prompt" class="rounded-lg border bg-white p-4">
          <summary class="cursor-pointer select-none text-sm font-medium text-gray-900">Prompt</summary>
          <div v-if="ad.promptVersion" class="mt-2 text-xs text-gray-600">Version: {{ ad.promptVersion }}</div>
          <pre class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-800">{{ ad.prompt }}</pre>
        </details>

        <details v-if="isAdmin && ad.inputImagePaths?.length" class="rounded-lg border bg-white p-4">
          <summary class="cursor-pointer select-none text-sm font-medium text-gray-900">Input billeder</summary>
          <div class="mt-3 grid gap-2 text-xs text-gray-700">
            <div v-for="(p, idx) in ad.inputImagePaths" :key="p" class="font-mono break-all">{{ idx + 1 }}. {{ p }}</div>
          </div>
        </details>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getAd, getMe, listIntegrationInstances, toAbsoluteBackendUrl, updateAd, updateIntegrationInstance, type Ad, type IntegrationInstance } from '../lib/api'

const route = useRoute()

const ad = ref<Ad | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const isAdmin = ref(false)

const editingTargetUrl = ref(false)
const savingTargetUrl = ref(false)
const targetUrlDraft = ref('')

function startEditTargetUrl() {
  editingTargetUrl.value = true
  targetUrlDraft.value = String(ad.value?.targetUrl ?? '')
}

function cancelEditTargetUrl() {
  editingTargetUrl.value = false
  targetUrlDraft.value = ''
}

async function saveTargetUrl() {
  if (!ad.value) return

  savingTargetUrl.value = true
  error.value = null
  try {
    const draft = targetUrlDraft.value.trim()
    const res = await updateAd(ad.value.id, {
      target_url: draft !== '' ? draft : null,
    })
    ad.value = res.ad
    editingTargetUrl.value = false
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Kunne ikke opdatere target URL.'
  } finally {
    savingTargetUrl.value = false
  }
}

const instanceById = ref<Record<number, IntegrationInstance>>({})
const togglingInstanceId = ref<number | null>(null)

async function toggleIntegration(instanceId: number) {
  const current = instanceById.value[instanceId]
  if (!current) return

  togglingInstanceId.value = instanceId
  try {
    const nextIsActive = !Boolean(current.is_active)
    const res = await updateIntegrationInstance(instanceId, {
      integration_key: current.integration_key,
      name: current.name,
      is_active: nextIsActive,
      config: current.config ?? null,
    })

    instanceById.value = {
      ...instanceById.value,
      [instanceId]: res.instance,
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Kunne ikke opdatere integration.'
  } finally {
    togglingInstanceId.value = null
  }
}

function formatDateTime(iso: string): string {
  try {
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return iso
    return d.toLocaleString('da-DK')
  } catch {
    return iso
  }
}

const tokenSummary = computed(() => {
  if (!ad.value) return null
  const prompt = typeof ad.value.promptTokens === 'number' ? ad.value.promptTokens : null
  const output = typeof ad.value.outputTokens === 'number' ? ad.value.outputTokens : null
  const total = typeof ad.value.totalTokens === 'number' ? ad.value.totalTokens : null

  if (prompt === null && output === null && total === null) return null

  return {
    prompt: prompt ?? 0,
    output: output ?? 0,
    total: total ?? 0,
  }
})

async function load() {
  loading.value = true
  error.value = null

  try {
    const id = String(route.params.id || '').trim()
    if (!id) {
      error.value = 'Mangler annonce-id.'
      ad.value = null
      return
    }

    const [meRes, adRes, instancesRes] = await Promise.all([getMe(), getAd(id), listIntegrationInstances()])
    isAdmin.value = Boolean(meRes?.user?.is_admin)
    ad.value = adRes.ad

    const byId: Record<number, IntegrationInstance> = {}
    for (const inst of instancesRes.instances ?? []) {
      byId[inst.id] = inst
    }
    instanceById.value = byId
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Kunne ikke hente annoncen.'
    ad.value = null
    isAdmin.value = false
    instanceById.value = {}
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  load().catch(() => {})
})
</script>
