<template>
  <div class="space-y-6">
    <form @submit.prevent="onSubmit" class="space-y-6">
      <div class="grid gap-6 rounded-lg border bg-white p-4">
        <div>
          <h2 class="text-lg font-semibold">Firmaoplysninger</h2>
          <p class="mt-1 text-sm text-gray-600">Beskriv dit firma og din målgruppe, så AI'en kan skrive bedre annoncetekster.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="companyName">Firmanavn</label>
          <input
            id="companyName"
            v-model="state.companyName"
            class="w-full rounded border px-3 py-2"
            required
          />
          <p class="text-xs text-gray-600">Dit officielle firmanavn.</p>
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
          <p class="text-xs text-gray-600">Angiv dit firmas hjemmeside.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="companyDescription">Firma beskrivelse</label>
          <textarea
            id="companyDescription"
            v-model="state.companyDescription"
            class="min-h-24 w-full rounded border px-3 py-2"
          />
          <p class="text-xs text-gray-600">Beskriv kort, hvad dit firma laver. Bruges af AI'en til at skrive annoncetekster.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="audienceDescription">Målgruppe beskrivelse</label>
          <textarea
            id="audienceDescription"
            v-model="state.audienceDescription"
            class="min-h-24 w-full rounded border px-3 py-2"
          />
          <p class="text-xs text-gray-600">Hvem er dine kunder? Beskriv dem her, så AI'en kan ramme den rigtige tone.</p>
        </div>
      </div>

      <details class="group grid gap-6 rounded-lg border bg-white p-4" open>
        <summary class="cursor-pointer list-none [&::-webkit-details-marker]:hidden">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold">Branding</h2>
              <p class="mt-1 text-sm text-gray-600">Definér farver, logo og øvrige branding-elementer.</p>
            </div>

            <svg
              class="h-5 w-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
        </summary>

        <fieldset class="grid gap-4 rounded border bg-gray-50 p-4">
          <div>
            <legend class="text-sm font-semibold text-gray-900">Farver</legend>
            <p class="mt-1 text-xs text-gray-600">Vælg 2 primære farver og (valgfrit) 2 sekundære farver.</p>
          </div>

          <div class="grid gap-4 md:grid-cols-4">
            <div class="grid gap-2">
              <label class="text-sm font-medium" for="primaryColor1">Primær farve 1</label>
              <input id="primaryColor1" v-model="state.primaryColor1" class="h-10 w-full" type="color" required />
            </div>
            <div class="grid gap-2">
              <label class="text-sm font-medium" for="primaryColor2">Primær farve 2</label>
              <input id="primaryColor2" v-model="state.primaryColor2" class="h-10 w-full" type="color" required />
            </div>
            <div class="grid gap-2">
              <label class="text-sm font-medium" for="primaryColor3">Sekundær 1</label>
              <input id="primaryColor3" v-model="state.primaryColor3" class="h-10 w-full" type="color" />
            </div>
            <div class="grid gap-2">
              <label class="text-sm font-medium" for="primaryColor4">Sekundær 2</label>
              <input id="primaryColor4" v-model="state.primaryColor4" class="h-10 w-full" type="color" />
            </div>
          </div>
        </fieldset>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="logo">Logo (jpg/png/webp)</label>
          <input id="logo" class="w-full" type="file" accept=".png,.jpg,.jpeg,.svg,.webp" @change="onFile" />
          <p class="text-xs text-gray-600">Upload dit logo. Kvadratiske formater virker bedst.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="fonts">Skrifttyper</label>
          <input
            id="fonts"
            v-model="state.fonts"
            class="w-full rounded border px-3 py-2"
          />
          <p class="text-xs text-gray-600">Angiv navnene på dine brand-skrifttyper, f.eks. 'Montserrat' eller 'Lato'.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="slogan">Slogan</label>
          <input
            id="slogan"
            v-model="state.slogan"
            class="w-full rounded border px-3 py-2"
          />
          <p class="text-xs text-gray-600">Dit firmas officielle slogan eller tagline.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="visual_guidelines">Beskrivelse (visuelle guidelines)</label>
          <textarea
            id="visual_guidelines"
            v-model="state.visual_guidelines"
            class="min-h-24 w-full rounded border px-3 py-2"
          />
          <p class="text-xs text-gray-600">Her kan du beskrive specifikke ønsker til det visuelle udtryk. F.eks. 'Brug altid en lys baggrund' eller 'Placer logoet i øverste højre hjørne'.</p>
        </div>
      </details>

      <details class="group grid gap-6 rounded-lg border bg-white p-4">
        <summary class="cursor-pointer list-none [&::-webkit-details-marker]:hidden">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold">Abonnement</h2>
              <p class="mt-1 text-sm text-gray-600">Overblik over din nuværende pakke og dit token-forbrug denne måned.</p>
            </div>

            <svg
              class="h-5 w-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
        </summary>

        <div class="grid gap-4 md:grid-cols-2">
          <fieldset class="grid gap-4 rounded border bg-gray-50 p-4">
            <div>
              <legend class="text-sm font-semibold text-gray-900">Subscription</legend>
            </div>

            <div v-if="subscriptionStatus" class="grid gap-2 text-sm">
              <div>
                <div class="text-xs text-gray-600">Pakke</div>
                <div class="font-semibold">{{ subscriptionStatus.plan?.name ?? '-' }}</div>
              </div>

              <div>
                <div class="text-xs text-gray-600">Status</div>
                <div class="mt-1">
                  <span
                    v-if="subscriptionStatus.status === 'active'"
                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                  >Aktiv</span>
                  <span
                    v-else-if="subscriptionStatus.status === 'expired'"
                    class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20"
                  >Udløbet</span>
                  <span
                    v-else
                    class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20"
                  >Ingen</span>
                </div>
              </div>

              <div v-if="subscriptionStatus.status !== 'none'">
                <div class="text-xs text-gray-600">Token limit pr. måned</div>
                <div class="font-semibold">{{ subscriptionStatus.tokens_limit.toLocaleString('da-DK') }}</div>
              </div>
            </div>

            <div v-else class="text-sm text-gray-600">Indlæser...</div>
          </fieldset>

          <fieldset class="grid gap-4 rounded border bg-gray-50 p-4">
            <div>
              <legend class="text-sm font-semibold text-gray-900">Tokens (denne måned)</legend>
            </div>

            <div v-if="tokensSummary" class="grid gap-3 text-sm">
              <div class="grid grid-cols-3 gap-3">
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

              <div class="grid gap-2">
                <div class="flex items-center justify-between text-xs text-gray-600">
                  <div>{{ Math.round(tokensSummary.usage_percentage) }}%</div>
                  <div>{{ tokensSummary.period }}</div>
                </div>
                <div class="h-2 w-full rounded bg-gray-200">
                  <div
                    class="h-2 rounded"
                    :class="tokensSummary.usage_percentage > 80 ? 'bg-red-600' : tokensSummary.usage_percentage > 60 ? 'bg-yellow-500' : 'bg-green-600'"
                    :style="{ width: `${Math.min(100, Math.max(0, tokensSummary.usage_percentage))}%` }"
                  />
                </div>
              </div>

              <div v-if="tokensSummary.status === 'none'" class="rounded bg-yellow-50 p-3 text-sm text-yellow-900">
                Ingen aktivt abonnement fundet
              </div>
            </div>

            <div v-else class="text-sm text-gray-600">Indlæser...</div>
          </fieldset>
        </div>
      </details>

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
import { getBrand, getSubscription, getTokensSummary, saveBrand, toAbsoluteBackendUrl } from '../lib/api'

const state = ref({
  companyName: '',
  websiteUrl: '',
  companyDescription: '',
  audienceDescription: '',
  primaryColor1: '#0ea5e9',
  primaryColor2: '#111827',
  primaryColor3: '#ffffff',
  primaryColor4: '#000000',
  fonts: '',
  slogan: '',
  visual_guidelines: '',
})

const logoFile = ref<File | null>(null)
const logoUrl = ref<string | null>(null)
const saving = ref(false)
const message = ref<string | null>(null)

const subscriptionStatus = ref<Awaited<ReturnType<typeof getSubscription>>['subscription'] | null>(null)
const tokensSummary = ref<Awaited<ReturnType<typeof getTokensSummary>> | null>(null)

function onFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  logoFile.value = file
}

async function load() {
  const [brand, subscriptionResponse, tokensSummaryResponse] = await Promise.all([
    getBrand(),
    getSubscription(),
    getTokensSummary(),
  ])
  state.value.companyName = brand.companyName ?? ''
  state.value.websiteUrl = brand.websiteUrl ?? ''
  state.value.companyDescription = brand.companyDescription ?? ''
  state.value.audienceDescription = brand.audienceDescription ?? ''
  state.value.primaryColor1 = brand.primaryColor1 ?? '#0ea5e9'
  state.value.primaryColor2 = brand.primaryColor2 ?? '#111827'
  state.value.primaryColor3 = brand.primaryColor3 ?? '#ffffff'
  state.value.primaryColor4 = brand.primaryColor4 ?? '#000000'
  state.value.fonts = brand.fonts ?? ''
  state.value.slogan = brand.slogan ?? ''
  state.value.visual_guidelines = brand.visual_guidelines ?? ''
  logoUrl.value = brand.logoPath ? toAbsoluteBackendUrl(brand.logoPath) : null

  subscriptionStatus.value = subscriptionResponse.subscription
  tokensSummary.value = tokensSummaryResponse
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
    form.set('fonts', state.value.fonts)
    form.set('slogan', state.value.slogan)
    form.set('visual_guidelines', state.value.visual_guidelines)
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