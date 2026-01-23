<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <h1 class="text-xl font-semibold">Vælg company</h1>
    <p class="mt-1 text-sm text-gray-600">Du har adgang til flere companies. Vælg hvilket du vil arbejde i.</p>

    <form class="mt-6 grid gap-6" @submit.prevent="onSubmit">
      <div class="grid gap-3">
        <label
          v-for="c in companies"
          :key="c.id"
          class="flex cursor-pointer items-center gap-4 rounded-lg border p-2 has-[:checked]:border-green-600 has-[:checked]:bg-green-50"
        >
          <img
            v-if="c.logo_path"
            :src="toAbsoluteBackendUrl(c.logo_path)"
            class="h-16 w-16 rounded-full object-contain"
          />
          <div v-else class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-sm">?</div>
          <div class="font-medium">{{ c.name }}</div>
          <input type="radio" v-model.number="companyId" :value="c.id" class="ml-auto" />
        </label>
      </div>

      <div class="flex items-center gap-3">
        <button
          class="flex w-full justify-center rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 sm:w-auto"
          type="submit"
          :disabled="!companyId"
        >
          Continue
        </button>
        <div v-if="message" class="text-sm text-gray-700">{{ message }}</div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getMe, setActiveCompanyId, toAbsoluteBackendUrl, type MeResponse } from '../lib/api'

const router = useRouter()

const companies = ref<MeResponse['companies']>([])
const companyId = ref<number | null>(null)
const message = ref<string | null>(null)

async function load() {
  const me = await getMe()
  companies.value = me.companies
  companyId.value = me.companies[0]?.id ?? null
}

async function onSubmit() {
  if (!companyId.value) {
    message.value = 'Vælg et company'
    return
  }

  setActiveCompanyId(companyId.value)
  await router.replace('/')
}

onMounted(() => {
  load().catch((e) => {
    message.value = e instanceof Error ? e.message : 'Kunne ikke hente companies'
  })
})
</script>
