<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <h1 class="text-xl font-semibold">Vælg company</h1>
    <p class="mt-1 text-sm text-gray-600">Du har adgang til flere companies. Vælg hvilket du vil arbejde i.</p>

    <div class="mt-6 grid gap-6">
      <div class="grid gap-3">
        <button
          v-for="c in companies"
          :key="c.id"
          class="flex w-full items-center gap-4 rounded-lg border p-2 text-left hover:border-green-600 hover:bg-green-50"
          :class="companyId === c.id ? 'border-green-600 bg-green-50' : ''"
          type="button"
          @click="selectCompany(c.id)"
        >
          <img
            v-if="c.logo_path && !failedLogoCompanyIds.has(c.id)"
            :src="toAbsoluteBackendUrl(c.logo_path)"
            class="h-16 w-16 rounded-full object-contain"
            @error="onLogoError(c.id)"
          />
          <div
            v-else
            class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600"
          >
            {{ (c.name || '?').trim().slice(0, 2).toUpperCase() }}
          </div>
          <div class="font-medium">{{ c.name }}</div>
        </button>
      </div>

      <div v-if="message" class="text-sm text-gray-700">{{ message }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { activeCompanyId, getMe, setActiveCompanyId, toAbsoluteBackendUrl, type MeResponse } from '../lib/api'

const router = useRouter()

const companies = ref<MeResponse['companies']>([])
const companyId = ref<number | null>(null)
const message = ref<string | null>(null)
const selecting = ref(false)

const failedLogoCompanyIds = ref<Set<number>>(new Set())

function onLogoError(companyId: number) {
  failedLogoCompanyIds.value = new Set([...failedLogoCompanyIds.value, companyId])
}

async function load() {
  const me = await getMe()
  companies.value = me.companies

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
