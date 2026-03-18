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
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white shadow-sm hover:bg-purple-700 disabled:opacity-50"
              :disabled="optimizingText || text.trim() === ''"
              :title="optimizingText ? 'Optimerer...' : 'Optimer tekst med AI'"
              @click="onOptimizeText"
            >
              <svg class="h-4 w-4" :class="optimizingText ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path fill="currentColor" d="M9.107 5.448c.598-1.75 3.016-1.803 3.725-.159l.06.16l.807 2.36a4 4 0 0 0 2.276 2.411l.217.081l2.36.806c1.75.598 1.803 3.016.16 3.725l-.16.06l-2.36.807a4 4 0 0 0-2.412 2.276l-.081.216l-.806 2.361c-.598 1.75-3.016 1.803-3.724.16l-.062-.16l-.806-2.36a4 4 0 0 0-2.276-2.412l-.216-.081l-2.36-.806c-1.751-.598-1.804-3.016-.16-3.724l.16-.062l2.36-.806A4 4 0 0 0 8.22 8.025l.081-.216zM11 6.094l-.806 2.36a6 6 0 0 1-3.49 3.649l-.25.091l-2.36.806l2.36.806a6 6 0 0 1 3.649 3.49l.091.25l.806 2.36l.806-2.36a6 6 0 0 1 3.49-3.649l.25-.09l2.36-.807l-2.36-.806a6 6 0 0 1-3.649-3.49l-.09-.25zM19 2a1 1 0 0 1 .898.56l.048.117l.35 1.026l1.027.35a1 1 0 0 1 .118 1.845l-.118.048l-1.026.35l-.35 1.027a1 1 0 0 1-1.845.117l-.048-.117l-.35-1.026l-1.027-.35a1 1 0 0 1-.118-1.845l.118-.048l1.026-.35l.35-1.027A1 1 0 0 1 19 2" />
              </svg>
            </button>
            <SpeechToTextButton v-model="text" @error="onSpeechError" />
          </div>
        </div>
        <div class="border-t px-4 py-4">
          <div class="relative">
            <textarea id="text" v-model="text" class="min-h-[14rem] w-full rounded border px-3 py-2" :class="optimizingText ? 'bg-gray-200' : 'bg-gray-50'" :readonly="optimizingText"></textarea>
            <div v-if="optimizingText" class="absolute inset-0 flex items-center justify-center rounded bg-gray-900/50 backdrop-blur-sm">
              <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 shadow-lg">
                <svg class="h-5 w-5 animate-spin text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M9.107 5.448c.598-1.75 3.016-1.803 3.725-.159l.06.16l.807 2.36a4 4 0 0 0 2.276 2.411l.217.081l2.36.806c1.75.598 1.803 3.016.16 3.725l-.16.06l-2.36.807a4 4 0 0 0-2.412 2.276l-.081.216l-.806 2.361c-.598 1.75-3.016 1.803-3.724.16l-.062-.16l-.806-2.36a4 4 0 0 0-2.276-2.412l-.216-.081l-2.36-.806c-1.751-.598-1.804-3.016-.16-3.724l.16-.062l2.36-.806A4 4 0 0 0 8.22 8.025l.081-.216zM11 6.094l-.806 2.36a6 6 0 0 1-3.49 3.649l-.25.091l-2.36.806l2.36.806a6 6 0 0 1 3.649 3.49l.091.25l.806 2.36l.806-2.36a6 6 0 0 1 3.49-3.649l.25-.09l2.36-.807l-2.36-.806a6 6 0 0 1-3.649-3.49l-.09-.25zM19 2a1 1 0 0 1 .898.56l.048.117l.35 1.026l1.027.35a1 1 0 0 1 .118 1.845l-.118.048l-1.026.35l-.35 1.027a1 1 0 0 1-1.845.117l-.048-.117l-.35-1.026l-1.027-.35a1 1 0 0 1-.118-1.845l.118-.048l1.026-.35l.35-1.027A1 1 0 0 1 19 2" />
                </svg>
                <span class="text-sm font-medium text-gray-900">AI optimerer teksten...</span>
              </div>
            </div>
          </div>
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

      <details class="group rounded-xl border bg-white" :open="scrapeOpen">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 select-none" @click.prevent="toggleScrapeOpen">
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-green-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2a10 10 0 1 0 10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              <path d="M22 12h-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="text-sm font-medium text-gray-900">Hent indhold fra web (BETA)</div>
          </div>
          <svg class="h-5 w-5 text-gray-500 transition-transform" :class="scrapeOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </summary>

        <div class="border-t px-4 py-4 space-y-4">
          <div class="rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-800">
            <strong>BETA:</strong> Denne funktion er i beta og vil ikke virke med alle websider. Nogle sider blokerer automatisk hentning af indhold.
          </div>
          <div class="grid gap-2">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
              <input v-model="scrapeUrlInput" class="h-10 w-full rounded border bg-gray-50 px-3 text-sm" type="url" placeholder="https://example.com" />
              <button
                class="inline-flex items-center justify-center rounded bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50"
                type="button"
                :disabled="scraping || scrapeUrlInput.trim() === ''"
                @click="onScrape"
              >
                Hent indhold
              </button>
            </div>
            <div class="text-xs text-gray-600">
              AI'en vil automatisk markere anbefalet tekst og vælge billeder. Du kan ændre valget bagefter.
            </div>
          </div>

          <div v-if="scrapeError" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ scrapeError }}
          </div>

          <div v-if="scraping" class="flex items-center gap-2 text-sm text-gray-700">
            <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" />
            <span>Henter indhold...</span>
          </div>

          <div v-else-if="scrapeResult" class="space-y-4">
            <div class="rounded border bg-blue-50 p-3 text-sm text-blue-900">
              Tip: Markér tekst med musen for at tilføje til udvalget. Klik på markeret tekst for at fjerne markeringen.
            </div>

            <div class="flex items-center justify-between">
              <div class="text-sm font-medium text-gray-900">Hentet tekst ({{ selectedTextSpans.length }} markeringer)</div>
              <button class="text-sm font-medium text-gray-700 hover:text-gray-900" type="button" @click="clearSelectedText">
                Ryd markeringer
              </button>
            </div>

            <div class="rounded border bg-gray-50">
              <div
                ref="scrapeTextBoxRef"
                class="max-h-80 overflow-auto p-3 text-sm leading-6 text-gray-900 whitespace-pre-wrap"
                @mouseup="onTextMouseUp"
              >
                <template v-for="(seg, idx) in renderedTextSegments" :key="idx">
                  <mark
                    v-if="seg.selected"
                    class="bg-yellow-200 cursor-pointer"
                    @click.prevent.stop="removeSpanByIndex(seg.start, seg.end)"
                  >{{ seg.text }}</mark>
                  <template v-else>{{ seg.text }}</template>
                </template>
              </div>
            </div>

            <div>
              <div class="text-sm font-medium text-gray-900">Vælg billeder - op til 5 ({{ selectedScrapeImageUrls.length }} valgt)</div>
              <div class="mt-3 grid grid-cols-3 gap-2 md:grid-cols-6">
                <button
                  v-for="img in scrapeResult.images"
                  :key="img.url"
                  type="button"
                  class="relative overflow-hidden rounded border bg-white"
                  :class="selectedScrapeImageUrls.includes(img.url) ? 'ring-2 ring-purple-600' : ''"
                  @click="toggleScrapeImage(img.url)"
                >
                  <img :src="img.url" class="aspect-square w-full object-cover" />
                  <div
                    class="absolute right-1 top-1 h-5 w-5 rounded-full border flex items-center justify-center"
                    :class="selectedScrapeImageUrls.includes(img.url) ? 'border-purple-600 bg-purple-600' : 'border-gray-300 bg-white'"
                  >
                    <svg v-if="selectedScrapeImageUrls.includes(img.url)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </div>
                </button>
              </div>
            </div>

            <div class="flex justify-end">
              <button
                class="inline-flex items-center justify-center rounded bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50"
                type="button"
                :disabled="applyingScrape"
                @click="applyScrapedContent"
              >
                Brug valgt indhold
              </button>
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

import { createAd, getAd, listAllowedAdSizes, optimizeText, refreshTokensSummary, scrapeUrl, tokensSummary, toAbsoluteBackendUrl, type Ad, type AdCreateDebug, type ScrapeRecommendedSpan, type ScrapeResult } from '../lib/api'
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
const optimizingText = ref(false)
const optimizeError = ref<string | null>(null)

const minRequiredTokens = 1000

type SelectedImageItem = { id: string; file: File; url: string }

const selectedImageItems = ref<SelectedImageItem[]>([])
const dragSourceId = ref<string | null>(null)

const scrapeOpen = ref(false)
const scrapeUrlInput = ref('')
const scraping = ref(false)
const scrapeError = ref<string | null>(null)
const scrapeResult = ref<ScrapeResult | null>(null)
const selectedTextSpans = ref<ScrapeRecommendedSpan[]>([])
const selectedScrapeImageUrls = ref<string[]>([])
const applyingScrape = ref(false)

const scrapeTextBoxRef = ref<HTMLElement | null>(null)

function toggleScrapeOpen() {
  scrapeOpen.value = !scrapeOpen.value
}

function clearSelectedText() {
  selectedTextSpans.value = []
}

function normalizeSpan(span: ScrapeRecommendedSpan): ScrapeRecommendedSpan {
  const start = Math.max(0, Math.floor(Number(span.start)))
  const end = Math.max(start, Math.floor(Number(span.end)))
  return { start, end }
}

function addSpan(next: ScrapeRecommendedSpan) {
  const normalized = normalizeSpan(next)
  if (normalized.end <= normalized.start) return

  const spans = [...selectedTextSpans.value, normalized]
    .map(normalizeSpan)
    .filter((s) => s.end > s.start)
    .sort((a, b) => a.start - b.start)

  const merged: ScrapeRecommendedSpan[] = []
  for (const s of spans) {
    const last = merged[merged.length - 1]
    if (!last) {
      merged.push({ ...s })
      continue
    }
    if (s.start <= last.end) {
      last.end = Math.max(last.end, s.end)
      continue
    }
    merged.push({ ...s })
  }
  selectedTextSpans.value = merged
}

function removeSpanByIndex(start: number, end: number) {
  selectedTextSpans.value = selectedTextSpans.value.filter((s) => !(s.start === start && s.end === end))
}

function getOffsetFromNode(root: Node, node: Node, nodeOffset: number): number {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT)
  let offset = 0
  let current = walker.nextNode()
  while (current) {
    if (current === node) {
      return offset + nodeOffset
    }
    offset += current.textContent?.length ?? 0
    current = walker.nextNode()
  }
  return offset
}

function onTextMouseUp() {
  const root = scrapeTextBoxRef.value
  if (!root) return
  const sel = window.getSelection()
  if (!sel || sel.rangeCount < 1) return
  const range = sel.getRangeAt(0)
  if (!range || range.collapsed) return
  if (!root.contains(range.startContainer) || !root.contains(range.endContainer)) return

  const start = getOffsetFromNode(root, range.startContainer, range.startOffset)
  const end = getOffsetFromNode(root, range.endContainer, range.endOffset)
  const a = Math.min(start, end)
  const b = Math.max(start, end)
  addSpan({ start: a, end: b })
  sel.removeAllRanges()
}

const renderedTextSegments = computed(() => {
  const full = String(scrapeResult.value?.full_text ?? '')
  const spans = [...selectedTextSpans.value].sort((a, b) => a.start - b.start)
  const out: { text: string; selected: boolean; start: number; end: number }[] = []
  let cursor = 0
  for (const s of spans) {
    const start = Math.max(0, Math.min(full.length, s.start))
    const end = Math.max(start, Math.min(full.length, s.end))
    if (start > cursor) {
      out.push({ text: full.slice(cursor, start), selected: false, start: cursor, end: start })
    }
    if (end > start) {
      out.push({ text: full.slice(start, end), selected: true, start, end })
    }
    cursor = end
  }
  if (cursor < full.length) {
    out.push({ text: full.slice(cursor), selected: false, start: cursor, end: full.length })
  }
  return out
})

async function onScrape() {
  const url = scrapeUrlInput.value.trim()
  if (url === '') return

  scraping.value = true
  scrapeError.value = null
  scrapeResult.value = null
  selectedTextSpans.value = []
  selectedScrapeImageUrls.value = []

  try {
    const res = await scrapeUrl(url)
    scrapeResult.value = res.result
    const spans = (res.recommended_text_spans ?? []).map((s) => ({ start: Number(s.start), end: Number(s.end) }))
    selectedTextSpans.value = spans

    const imageUrls = (res.result.images ?? []).map((x) => x.url).filter((x) => typeof x === 'string' && x.trim() !== '')
    selectedScrapeImageUrls.value = imageUrls.slice(0, 3)
  } catch (e: any) {
    console.error('Scrape error:', e)
    const errorMessage = e?.response?.data?.message || e?.message || 'Kunne ikke hente indhold fra URL'
    scrapeError.value = errorMessage
  } finally {
    scraping.value = false
  }
}

function toggleScrapeImage(url: string) {
  const idx = selectedScrapeImageUrls.value.indexOf(url)
  if (idx >= 0) {
    selectedScrapeImageUrls.value = selectedScrapeImageUrls.value.filter((x) => x !== url)
    return
  }
  if (selectedScrapeImageUrls.value.length >= 5) return
  selectedScrapeImageUrls.value = [...selectedScrapeImageUrls.value, url]
}

async function downloadAsFile(url: string, idx: number): Promise<File | null> {
  try {
    const proxyUrl = `/api/proxy-image?url=${encodeURIComponent(url)}`
    const res = await fetch(proxyUrl)
    if (!res.ok) return null
    const blob = await res.blob()
    const contentType = blob.type || 'image/jpeg'
    const ext = contentType.includes('png') ? 'png' : contentType.includes('webp') ? 'webp' : 'jpg'
    return new File([blob], `scraped-${idx + 1}.${ext}`, { type: contentType })
  } catch {
    return null
  }
}

async function applyScrapedContent() {
  if (!scrapeResult.value) return
  applyingScrape.value = true
  try {
    const full = String(scrapeResult.value.full_text ?? '')
    const spans = [...selectedTextSpans.value].sort((a, b) => a.start - b.start)
    const parts: string[] = []
    for (const s of spans) {
      const start = Math.max(0, Math.min(full.length, s.start))
      const end = Math.max(start, Math.min(full.length, s.end))
      const chunk = full.slice(start, end).trim()
      if (chunk !== '') parts.push(chunk)
    }
    if (parts.length > 0) {
      text.value = parts.join('\n\n')
    }

    const maxImages = 5
    const availableSlots = maxImages - selectedImageItems.value.length
    if (availableSlots > 0) {
      const imageUrls = selectedScrapeImageUrls.value.slice(0, availableSlots)
      const downloadResults = await Promise.all(imageUrls.map((u, idx) => downloadAsFile(u, idx)))
      const files = downloadResults.filter((x): x is File => x instanceof File)

      const timestamp = Date.now()
      const newItems = files.map((file, idx) => {
        const blobUrl = URL.createObjectURL(file)
        return {
          id: `${timestamp}-${idx}`,
          file,
          url: blobUrl,
        }
      })

      selectedImageItems.value = [...selectedImageItems.value, ...newItems]
    }

    scrapeOpen.value = false
  } finally {
    applyingScrape.value = false
  }
}

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

function onSpeechError(error: string) {
  speechError.value = error
}

async function onOptimizeText() {
  const currentText = text.value.trim()
  if (currentText === '') return

  optimizingText.value = true
  optimizeError.value = null
  speechError.value = null

  try {
    const result = await optimizeText(currentText)
    text.value = result.optimized
  } catch (e) {
    optimizeError.value = e instanceof Error ? e.message : 'Kunne ikke optimere teksten.'
    speechError.value = optimizeError.value
  } finally {
    optimizingText.value = false
  }
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
