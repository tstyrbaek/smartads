<template>
  <div class="mx-auto max-w-md rounded-lg border bg-white p-6">
    <h1 class="text-xl font-semibold">Vælg company</h1>
    <p class="mt-1 text-sm text-gray-600">Du har adgang til flere companies. Vælg hvilket du vil arbejde i.</p>

    <form class="mt-6 grid gap-4" @submit.prevent="onSubmit">
      <div class="grid gap-2">
        <label class="text-sm font-medium" for="company">Company</label>
        <select id="company" v-model.number="companyId" class="w-full rounded border px-3 py-2">
          <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <div class="flex items-center gap-3">
        <button class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800" type="submit">
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
import { getMe, setActiveCompanyId } from '../lib/api'

const router = useRouter()

const companies = ref<{ id: number; name: string }[]>([])
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
