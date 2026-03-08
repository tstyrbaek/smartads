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
          ></textarea>
          <p class="text-xs text-gray-600">Beskriv kort, hvad dit firma laver. Bruges af AI'en til at skrive annoncetekster.</p>
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-medium" for="audienceDescription">Målgruppe beskrivelse</label>
          <textarea
            id="audienceDescription"
            v-model="state.audienceDescription"
            class="min-h-24 w-full rounded border px-3 py-2"
          ></textarea>
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
          <label class="text-sm font-medium" for="template">Design template (png/jpg/webp/pdf)</label>
          <input id="template" class="w-full" type="file" accept=".png,.jpg,.jpeg,.webp,.pdf" @change="onTemplateFile" />
          <p class="text-xs text-gray-600">Upload en reference til designstil (farver/typografi/layout). Bruges som stil-reference af AI'en.</p>
          <a v-if="templateUrl" class="text-xs text-blue-700 hover:underline" :href="templateUrl" target="_blank" rel="noreferrer">Se nuværende template</a>
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
          ></textarea>
          <p class="text-xs text-gray-600">Her kan du beskrive specifikke ønsker til det visuelle udtryk. F.eks. 'Brug altid en lys baggrund' eller 'Placer logoet i øverste højre hjørne'.</p>
        </div>
      </details>

      <details class="group grid gap-6 rounded-lg border bg-white p-4">
        <summary class="cursor-pointer list-none [&::-webkit-details-marker]:hidden">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold">Integrationer</h2>
              <p class="mt-1 text-sm text-gray-600">Opret og administrér integrationer (fx website embed).</p>
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

        <div v-if="integrationsLoading" class="text-sm text-gray-600">Indlæser...</div>

        <div v-else class="grid gap-4">
          <div class="flex items-center justify-between gap-3">
            <div class="text-sm text-gray-700">
              Integrationer bruges som targets, når du publicerer en annonce.
            </div>

            <button
              class="rounded bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800"
              type="button"
              @click="startCreateIntegration"
            >
              Tilføj integration
            </button>
          </div>

          <div v-if="integrationsMessage" class="text-sm text-gray-700">{{ integrationsMessage }}</div>

          <div v-if="integrationForm.open" class="grid gap-4 rounded border bg-gray-50 p-4">
            <div class="flex items-center justify-between">
              <div class="text-sm font-semibold text-gray-900">
                {{ integrationForm.mode === 'create' ? 'Opret integration' : 'Rediger integration' }}
              </div>
              <button type="button" class="text-xs text-gray-600 hover:text-gray-900" @click="closeIntegrationForm">Luk</button>
            </div>

            <div class="grid gap-2">
              <label class="text-sm font-medium" for="integrationKey">Integration</label>
              <select
                id="integrationKey"
                v-model="integrationForm.integrationKey"
                class="w-full rounded border px-3 py-2"
              >
                <option v-for="def in integrationDefinitions" :key="def.key" :value="def.key">
                  {{ def.name }}
                </option>
              </select>
            </div>

            <div v-if="!isNetworkWebsiteEmbed" class="grid gap-2">
              <label class="text-sm font-medium" for="integrationName">Navn</label>
              <input id="integrationName" v-model="integrationForm.name" class="w-full rounded border px-3 py-2" />
            </div>
            <div v-else class="grid gap-2">
              <div class="text-sm font-medium text-gray-700">Navn</div>
              <div class="rounded border bg-white px-3 py-2 text-sm text-gray-700">
                {{ selectedIntegrationDefinition?.name || 'Network embed' }}
              </div>
            </div>

            <div class="flex items-center gap-3">
              <input id="integrationActive" v-model="integrationForm.isActive" type="checkbox" class="h-4 w-4" />
              <label class="text-sm text-gray-700" for="integrationActive">Aktiv</label>
            </div>

            <div v-if="integrationForm.integrationKey === 'website_embed'" class="grid gap-4 rounded border bg-white p-4">
              <div>
                <div class="text-sm font-semibold text-gray-900">Embed</div>
                <p class="mt-1 text-xs text-gray-600">Brug embed-koden på dit website. Viser alle annoncer der er valgt til denne integration.</p>
              </div>

              <div class="grid gap-2">
                <label class="text-sm font-medium" for="embedAdSize">Annonceformat</label>
                <select id="embedAdSize" v-model="integrationForm.adSize" class="w-full rounded border px-3 py-2">
                  <option value="">Ingen krav</option>
                  <option v-for="s in allowedAdSizes" :key="`${s.width}x${s.height}`" :value="`${s.width}x${s.height}`">
                    {{ s.width }}×{{ s.height }}
                  </option>
                </select>
                <p class="text-xs text-gray-600">Hvis du vælger en størrelse, kan kun annoncer i den størrelse udgives til integrationen.</p>
              </div>

              <div class="grid gap-2">
                <label class="text-sm font-medium" for="embedViewMode">Visning</label>
                <select id="embedViewMode" v-model="integrationForm.viewMode" class="w-full rounded border px-3 py-2">
                  <option value="grid">Grid</option>
                  <option value="slideshow">Slideshow</option>
                </select>
                <p class="text-xs text-gray-600">Vælg hvordan annoncerne vises på websitet.</p>
              </div>

              <div v-if="integrationForm.viewMode === 'slideshow'" class="grid gap-4 rounded border bg-gray-50 p-4">
                <div class="grid gap-2">
                  <label class="text-sm font-medium" for="embedItemsPerView">Antal synlige</label>
                  <select id="embedItemsPerView" v-model.number="integrationForm.slideshowItemsPerView" class="w-full rounded border px-3 py-2">
                    <option v-for="n in 6" :key="n" :value="n">{{ n }}</option>
                  </select>
                  <p v-if="slideshowItemsPerViewError" class="text-xs text-red-600">{{ slideshowItemsPerViewError }}</p>
                </div>

                <div class="grid gap-2">
                  <label class="text-sm font-medium" for="embedInterval">Skift hvert (ms)</label>
                  <input
                    id="embedInterval"
                    v-model.number="integrationForm.slideshowIntervalMs"
                    class="w-full rounded border px-3 py-2"
                    type="number"
                    min="1500"
                    max="20000"
                    step="500"
                  />
                  <p class="text-xs text-gray-600">Angives i millisekunder (1500-20000). Autoplay og loop er altid slået til.</p>
                  <p v-if="slideshowIntervalError" class="text-xs text-red-600">{{ slideshowIntervalError }}</p>
                </div>
              </div>

              <div class="grid gap-2">
                <label class="text-sm font-medium" for="embedToken">Embed token</label>
                <input id="embedToken" v-model="integrationForm.embedToken" class="w-full rounded border px-3 py-2 font-mono text-xs" />
              </div>

              <div v-if="integrationForm.id" class="grid gap-2">
                <label class="text-sm font-medium" for="embedCode">Embed-kode</label>
                <textarea
                  id="embedCode"
                  readonly
                  class="min-h-16 w-full rounded border px-3 py-2 font-mono text-xs"
                  :value="getEmbedCode(integrationForm.id)"
                ></textarea>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <button
                class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                type="button"
                :disabled="integrationFormSaving || !!slideshowIntervalError || !!slideshowItemsPerViewError"
                @click="saveIntegration"
              >
                Gem
              </button>

              <button class="rounded border px-4 py-2 text-sm font-medium" type="button" @click="closeIntegrationForm">
                Annuller
              </button>

              <button
                v-if="integrationForm.mode === 'edit' && integrationForm.id"
                class="ml-auto rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                type="button"
                @click="deleteIntegration(integrationForm.id)"
              >
                Slet
              </button>
            </div>
          </div>

          <div v-if="integrationInstances.length === 0" class="text-sm text-gray-600">Ingen integrationer endnu.</div>

          <div v-else class="grid gap-3">
            <button
              v-for="inst in integrationInstances"
              :key="inst.id"
              type="button"
              class="flex items-start justify-between gap-4 rounded border bg-white p-4 text-left hover:bg-gray-50"
              @click="startEditIntegration(inst)"
            >
              <div class="min-w-0">
                <div class="font-semibold text-gray-900">{{ inst.name }}</div>
                <div class="text-xs text-gray-500">{{ inst.integration_key }}</div>
              </div>

              <div>
                <span
                  v-if="inst.is_active"
                  class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                >Aktiv</span>
                <span
                  v-else
                  class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20"
                >Inaktiv</span>
              </div>
            </button>
          </div>
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
                    :style="{ width: Math.min(100, Math.max(0, tokensSummary.usage_percentage)) + '%' }"
                  ></div>
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
          <div class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor1 }"></div>
          <div class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor2 }"></div>
          <div v-if="state.primaryColor3" class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor3 }"></div>
          <div v-if="state.primaryColor4" class="h-6 w-6 rounded" :style="{ backgroundColor: state.primaryColor4 }"></div>
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
import { computed, onMounted, ref, watch } from 'vue'
import {
  createIntegrationInstance,
  deleteIntegrationInstance,
  getBrand,
  listAllowedAdSizes,
  getSubscription,
  getTokensSummary,
  listIntegrationDefinitions,
  listIntegrationInstances,
  saveBrand,
  toAbsoluteBackendUrl,
  updateIntegrationInstance,
  type IntegrationDefinition,
  type IntegrationInstance,
} from '../lib/api'

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
const templateFile = ref<File | null>(null)
const templateUrl = ref<string | null>(null)
const saving = ref(false)
const message = ref<string | null>(null)

const subscriptionStatus = ref<Awaited<ReturnType<typeof getSubscription>>['subscription'] | null>(null)
const tokensSummary = ref<Awaited<ReturnType<typeof getTokensSummary>> | null>(null)

const integrationsLoading = ref(false)
const integrationsMessage = ref<string | null>(null)
const integrationDefinitions = ref<IntegrationDefinition[]>([])
const integrationInstances = ref<IntegrationInstance[]>([])
const integrationFormSaving = ref(false)

const allowedAdSizes = ref<{ width: number; height: number }[]>([])

const integrationForm = ref<{
  open: boolean
  mode: 'create' | 'edit'
  id: number | null
  integrationKey: string
  name: string
  isActive: boolean
  embedToken: string
  adSize: string
  viewMode: 'grid' | 'slideshow'
  slideshowItemsPerView: number
  slideshowIntervalMs: number
}>({
  open: false,
  mode: 'create',
  id: null,
  integrationKey: 'website_embed',
  name: '',
  isActive: true,
  embedToken: '',
  adSize: '',
  viewMode: 'grid',
  slideshowItemsPerView: 3,
  slideshowIntervalMs: 4000,
})

const selectedIntegrationDefinition = computed(() => {
  const key = integrationForm.value.integrationKey
  return integrationDefinitions.value.find((d) => d.key === key) ?? null
})

const isNetworkWebsiteEmbed = computed(() => {
  return (selectedIntegrationDefinition.value?.type ?? '') === 'network_website_embed'
})

watch(
  () => integrationForm.value.integrationKey,
  () => {
    if (isNetworkWebsiteEmbed.value) {
      integrationForm.value.name = selectedIntegrationDefinition.value?.name || 'Network embed'
    }
  },
)

const slideshowIntervalError = computed(() => {
  if (integrationForm.value.integrationKey !== 'website_embed') return null
  if (integrationForm.value.viewMode !== 'slideshow') return null
  const v = Number(integrationForm.value.slideshowIntervalMs)
  if (!Number.isFinite(v) || v < 1500 || v > 20000) return 'Interval skal være mellem 1500 og 20000 ms.'
  return null
})

const slideshowItemsPerViewError = computed(() => {
  if (integrationForm.value.integrationKey !== 'website_embed') return null
  if (integrationForm.value.viewMode !== 'slideshow') return null
  const v = Number(integrationForm.value.slideshowItemsPerView)
  if (!Number.isFinite(v) || v < 1 || v > 6) return 'Antal synlige skal være mellem 1 og 6.'
  return null
})

function onFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  logoFile.value = file
}

function onTemplateFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  templateFile.value = file
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
  templateUrl.value = brand.templatePath ? toAbsoluteBackendUrl(brand.templatePath) : null

  subscriptionStatus.value = subscriptionResponse.subscription
  tokensSummary.value = tokensSummaryResponse

  await loadIntegrations()
}

async function loadIntegrations() {
  integrationsLoading.value = true
  integrationsMessage.value = null
  try {
    const [defs, inst] = await Promise.all([
      listIntegrationDefinitions(),
      listIntegrationInstances(),
    ])
    integrationDefinitions.value = defs.definitions
    integrationInstances.value = inst.instances

    const sizes = await listAllowedAdSizes()
    allowedAdSizes.value = Array.isArray(sizes.sizes) ? sizes.sizes : []

    if (integrationDefinitions.value.length > 0 && !integrationDefinitions.value.some((d) => d.key === integrationForm.value.integrationKey)) {
      integrationForm.value.integrationKey = integrationDefinitions.value[0].key
    }
  } catch (e) {
    integrationsMessage.value = e instanceof Error ? e.message : 'Kunne ikke hente integrationer'
  } finally {
    integrationsLoading.value = false
  }
}

function startCreateIntegration() {
  integrationForm.value.open = true
  integrationForm.value.mode = 'create'
  integrationForm.value.id = null
  integrationForm.value.name = ''
  integrationForm.value.isActive = true
  integrationForm.value.embedToken = ''
  integrationForm.value.adSize = ''
  integrationForm.value.viewMode = 'grid'
  integrationForm.value.slideshowItemsPerView = 3
  integrationForm.value.slideshowIntervalMs = 4000

  if (integrationDefinitions.value.length > 0) {
    integrationForm.value.integrationKey = integrationDefinitions.value[0].key
  } else {
    integrationForm.value.integrationKey = 'website_embed'
  }
}

function startEditIntegration(inst: IntegrationInstance) {
  integrationForm.value.open = true
  integrationForm.value.mode = 'edit'
  integrationForm.value.id = inst.id
  integrationForm.value.integrationKey = inst.integration_key
  integrationForm.value.name = inst.name
  integrationForm.value.isActive = inst.is_active
  integrationForm.value.embedToken = String(inst.config?.embed_token ?? '')
  const w = inst.config?.ad_width
  const h = inst.config?.ad_height
  const wNum = w && !isNaN(Number(w)) ? String(parseInt(String(w), 10)) : ''
  const hNum = h && !isNaN(Number(h)) ? String(parseInt(String(h), 10)) : ''
  integrationForm.value.adSize = wNum && hNum ? `${wNum}x${hNum}` : ''
  integrationForm.value.viewMode = (inst.config?.view_mode === 'slideshow' ? 'slideshow' : 'grid')
  integrationForm.value.slideshowItemsPerView = Number(inst.config?.slideshow_items_per_view ?? 3)
  integrationForm.value.slideshowIntervalMs = Number(inst.config?.slideshow_interval_ms ?? 4000)
}

function closeIntegrationForm() {
  integrationForm.value.open = false
}

function getEmbedCode(instanceId: number) {
  const scriptUrl = toAbsoluteBackendUrl(`/embed/${instanceId}/script.js`)
  return `<script src="${scriptUrl}"></` + 'script>'
}

async function saveIntegration() {
  integrationsMessage.value = null
  const viewMode = integrationForm.value.viewMode === 'slideshow' ? 'slideshow' : 'grid'
  const itemsPerViewRaw = Number(integrationForm.value.slideshowItemsPerView)
  const intervalMsRaw = Number(integrationForm.value.slideshowIntervalMs)

  if (integrationForm.value.integrationKey === 'website_embed' && viewMode === 'slideshow') {
    if (!Number.isFinite(intervalMsRaw) || intervalMsRaw < 1500 || intervalMsRaw > 20000) {
      integrationsMessage.value = 'Interval skal angives i millisekunder mellem 1500 og 20000.'
      return
    }
    if (!Number.isFinite(itemsPerViewRaw) || itemsPerViewRaw < 1 || itemsPerViewRaw > 6) {
      integrationsMessage.value = 'Antal synlige skal være mellem 1 og 6.'
      return
    }
  }

  const itemsPerView = Number.isFinite(itemsPerViewRaw) ? Math.min(6, Math.max(1, Math.trunc(itemsPerViewRaw))) : 3
  const intervalMs = Number.isFinite(intervalMsRaw) ? Math.min(20000, Math.max(1500, Math.trunc(intervalMsRaw))) : 4000

  integrationFormSaving.value = true
  try {
    const name = isNetworkWebsiteEmbed.value ? (selectedIntegrationDefinition.value?.name || 'Network embed') : integrationForm.value.name

    const payload = {
      integration_key: integrationForm.value.integrationKey,
      name,
      is_active: integrationForm.value.isActive,
      config:
        integrationForm.value.integrationKey === 'website_embed'
          ? {
              embed_token: integrationForm.value.embedToken || null,
              ad_width: integrationForm.value.adSize ? Number(String(integrationForm.value.adSize).split('x')[0]) : null,
              ad_height: integrationForm.value.adSize ? Number(String(integrationForm.value.adSize).split('x')[1]) : null,
              view_mode: viewMode,
              slideshow_items_per_view: itemsPerView,
              slideshow_interval_ms: intervalMs,
            }
          : null,
    }

    if (integrationForm.value.mode === 'create') {
      await createIntegrationInstance(payload)
      integrationsMessage.value = 'Integration oprettet'
    } else if (integrationForm.value.id) {
      await updateIntegrationInstance(integrationForm.value.id, payload)
      integrationsMessage.value = 'Integration opdateret'
    }

    await loadIntegrations()
    closeIntegrationForm()
  } catch (e) {
    integrationsMessage.value = e instanceof Error ? e.message : 'Fejl'
  } finally {
    integrationFormSaving.value = false
  }
}

async function deleteIntegration(id: number) {
  if (!confirm('Er du sikker?')) return
  integrationsMessage.value = null
  integrationFormSaving.value = true
  try {
    await deleteIntegrationInstance(id)
    integrationsMessage.value = 'Integration slettet'
    await loadIntegrations()
    closeIntegrationForm()
  } catch (e) {
    integrationsMessage.value = e instanceof Error ? e.message : 'Fejl'
  } finally {
    integrationFormSaving.value = false
  }
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

    if (templateFile.value) {
      form.set('template', templateFile.value)
    }

    const saved = await saveBrand(form)
    logoUrl.value = saved.logoPath ? toAbsoluteBackendUrl(saved.logoPath) : logoUrl.value
    templateUrl.value = saved.templatePath ? toAbsoluteBackendUrl(saved.templatePath) : templateUrl.value
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