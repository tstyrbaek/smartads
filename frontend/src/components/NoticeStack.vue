<template>
  <div v-if="items.length" class="grid gap-3">
    <div
      v-for="item in items"
      :key="item.id"
      class="rounded-lg border bg-white p-4"
      :class="levelClass(item.level)"
    >
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="text-sm font-semibold">{{ item.title }}</div>
          <div v-if="item.message" class="mt-1 text-sm text-gray-700">
            {{ item.message }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  items: {
    type: Array,
    required: true,
    default: () => [],
  },
})

function levelClass(level) {
  if (level === 'success') return 'border-green-200 bg-green-50'
  if (level === 'warning') return 'border-yellow-200 bg-yellow-50'
  if (level === 'error') return 'border-red-200 bg-red-50'
  return 'border-blue-200 bg-blue-50'
}
</script>
