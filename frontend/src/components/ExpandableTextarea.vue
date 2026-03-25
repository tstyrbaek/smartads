<template>
  <div class="relative w-full">
    <textarea
      ref="textareaRef"
      :value="modelValue"
      v-bind="attrs"
      :style="textareaStyle"
      @input="onInput"
    ></textarea>

    <div v-if="hasOverflow || isExpanded" class="pointer-events-none absolute bottom-2 right-5">
      <button
        class="pointer-events-auto inline-flex h-6 w-6 items-center justify-center rounded bg-white/90 text-gray-500 shadow-sm ring-1 ring-gray-200 hover:bg-white hover:text-gray-800"
        type="button"
        :title="isExpanded ? 'Vis mindre' : 'Vis hele teksten'"
        @click="toggleExpanded"
      >
        <svg v-if="isExpanded" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M14.78 12.53a.75.75 0 0 1-1.06 0L10 8.81l-3.72 3.72a.75.75 0 0 1-1.06-1.06l4.25-4.25a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
        <span class="sr-only">{{ isExpanded ? 'Vis mindre' : 'Vis hele teksten' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useAttrs, watch } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])
const attrs = useAttrs()

const textareaRef = ref(null)
const isExpanded = ref(false)
const hasOverflow = ref(false)

const textareaStyle = computed(() => {
  if (!isExpanded.value || !textareaRef.value) {
    return {}
  }

  return {
    height: `${textareaRef.value.scrollHeight}px`,
    overflowY: 'hidden',
  }
})

function updateOverflowState() {
  if (!textareaRef.value) return

  const hasVerticalOverflow = textareaRef.value.scrollHeight > textareaRef.value.clientHeight + 1
  hasOverflow.value = hasVerticalOverflow

  if (!hasVerticalOverflow && isExpanded.value) {
    isExpanded.value = false
  }
}

function onInput(event) {
  emit('update:modelValue', event.target.value)
  nextTick(updateOverflowState)
}

function toggleExpanded() {
  isExpanded.value = !isExpanded.value
  nextTick(updateOverflowState)
}

watch(
  () => props.modelValue,
  () => {
    nextTick(updateOverflowState)
  },
)

onMounted(() => {
  updateOverflowState()
  window.addEventListener('resize', updateOverflowState)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateOverflowState)
})
</script>
