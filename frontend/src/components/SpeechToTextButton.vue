<template>
  <button
    class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
    type="button"
    :disabled="!isSupported"
    :title="buttonTitle"
    @click="toggle"
  >
    <span v-if="isListening" class="absolute inline-block h-2 w-2 translate-x-3 -translate-y-3 rounded-full bg-red-600" />

    <svg
      v-if="!isListening"
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="h-4 w-4"
      aria-hidden="true"
    >
      <path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3z" />
      <path d="M19 11a7 7 0 0 1-14 0" />
      <line x1="12" y1="19" x2="12" y2="23" />
      <line x1="8" y1="23" x2="16" y2="23" />
    </svg>

    <svg
      v-else
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="h-4 w-4"
      aria-hidden="true"
    >
      <rect x="7" y="7" width="10" height="10" rx="2" />
    </svg>
  </button>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'

type SpeechRecognitionConstructor = new () => SpeechRecognition

type SpeechRecognition = {
  lang: string
  continuous: boolean
  interimResults: boolean
  onresult: ((event: any) => void) | null
  onerror: ((event: any) => void) | null
  onend: (() => void) | null
  start: () => void
  stop: () => void
}

const props = withDefaults(
  defineProps<{
    modelValue: string
    lang?: string
    mode?: 'append' | 'replace'
  }>(),
  {
    lang: 'da-DK',
    mode: 'append',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'error', message: string): void
}>()

const isListening = ref(false)
let recognition: SpeechRecognition | null = null

const RecognitionCtor = computed<SpeechRecognitionConstructor | null>(() => {
  const w = window as any
  return (w.SpeechRecognition ?? w.webkitSpeechRecognition ?? null) as SpeechRecognitionConstructor | null
})

const isSupported = computed(() => Boolean(RecognitionCtor.value))

const buttonTitle = computed(() => {
  if (!isSupported.value) return 'Talegenkendelse er ikke understøttet i denne browser'
  if (isListening.value) return 'Stop optagelse'
  return 'Start optagelse'
})

function buildNextValue(transcript: string) {
  const cleaned = transcript.trim()
  if (!cleaned) return props.modelValue

  if (props.mode === 'replace') {
    return cleaned
  }

  const current = props.modelValue ?? ''
  if (!current.trim()) return cleaned
  return `${current.trim()} ${cleaned}`
}

function start() {
  if (!RecognitionCtor.value) return

  const next = new RecognitionCtor.value()
  next.lang = props.lang
  next.continuous = true
  next.interimResults = true

  next.onresult = (event: any) => {
    let finalText = ''

    for (let i = event.resultIndex; i < event.results.length; i += 1) {
      const result = event.results[i]
      const text = String(result?.[0]?.transcript ?? '')
      if (result?.isFinal) {
        finalText += text
      }
    }

    if (finalText) {
      emit('update:modelValue', buildNextValue(finalText))
    }
  }

  next.onerror = (event: any) => {
    const message = String(event?.error ?? 'Unknown speech error')
    emit('error', message)
    isListening.value = false
  }

  next.onend = () => {
    isListening.value = false
  }

  recognition = next
  isListening.value = true

  try {
    recognition.start()
  } catch (e) {
    isListening.value = false
    emit('error', e instanceof Error ? e.message : 'Could not start speech recognition')
  }
}

function stop() {
  if (!recognition) return
  try {
    recognition.stop()
  } finally {
    recognition = null
    isListening.value = false
  }
}

function toggle() {
  if (!isSupported.value) return
  if (isListening.value) {
    stop()
  } else {
    start()
  }
}

onBeforeUnmount(() => {
  stop()
})
</script>
