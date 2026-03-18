<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <h1 class="text-xl font-semibold">Vælg annoncør</h1>
    <p class="mt-1 text-sm text-gray-600">Du har adgang til flere annoncører. Vælg hvilken du vil arbejde med.</p>

    <div class="mt-6 grid gap-6">
      <div v-if="companies.length > 8" class="grid gap-2">
        <label class="text-xs font-medium text-gray-700 hidden">Søg</label>
        <input v-model="searchQuery" class="w-full rounded border bg-white px-3 py-2 text-sm" type="text" placeholder="Søg i annoncører..." />
      </div>

      <div class="grid gap-3">
        <button
          v-for="c in filteredCompanies"
          :key="c.id"
          class="flex w-full items-center gap-4 rounded-lg border p-2 text-left hover:border-green-600 hover:bg-green-50"
          :class="companyId === c.id ? 'border-green-600 bg-green-50' : ''"
          type="button"
          @click="selectCompany(c.id)"
        >
          <img
            v-if="c.logo_path && !failedLogoCompanyIds.has(c.id)"
            :src="toAbsoluteBackendUrl(c.logo_path)"
            class="h-8 w-16 rounded-full object-contain"
            @error="onLogoError(c.id)"
          />
          <div
            v-else
            class="flex h-8 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600"
          >
            {{ (c.name || '?').trim().slice(0, 2).toUpperCase() }}
          </div>
          <div class="font-medium">{{ c.name }}</div>
        </button>

        <div v-if="filteredCompanies.length === 0" class="rounded border bg-gray-50 p-3 text-sm text-gray-700">Ingen match.</div>
      </div>

      <div v-if="message" class="text-sm text-gray-700">{{ message }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { activeCompanyId, getMe, setActiveCompanyId, toAbsoluteBackendUrl, type MeResponse } from '../lib/api'

const router = useRouter()

const companies = ref<MeResponse['companies']>([])
const companyId = ref<number | null>(null)
const message = ref<string | null>(null)
const selecting = ref(false)

const searchQuery = ref('')

const filteredCompanies = computed(() => {
  const list = companies.value ?? []
  const q = searchQuery.value.trim().toLowerCase()
  if (q === '') return list
  return list.filter((c) => String(c.name ?? '').toLowerCase().includes(q))
})

const failedLogoCompanyIds = ref<Set<number>>(new Set())

function onLogoError(companyId: number) {
  failedLogoCompanyIds.value = new Set([...failedLogoCompanyIds.value, companyId])
}

async function load() {
  const me = await getMe()
  companies.value = me.companies
  searchQuery.value = ''

  const storedActiveId = activeCompanyId.value ? Number(activeCompanyId.value) : null
  const preferredId = storedActiveId && !Number.isNaN(storedActiveId) ? storedActiveId : null

  if (preferredId && me.companies.some((c) => c.id === preferredId)) {
    companyId.value = preferredId
    return
  }

  companyId.value = me.companies[0]?.id ?? null
}

async function selectCompany(id: number) {
  if (selecting.value) return

  selecting.value = true
  message.value = null
  companyId.value = id

  try {
    setActiveCompanyId(id)
    await router.replace('/')
  } catch (e) {
    message.value = e instanceof Error ? e.message : 'Kunne ikke vælge company'
  } finally {
    selecting.value = false
  }
}

onMounted(() => {
  load().catch((e) => {
    message.value = e instanceof Error ? e.message : 'Kunne ikke hente companies'
  })
})
</script>
