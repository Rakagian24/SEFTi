<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

const props = defineProps<{
  modelValue?: string | number
  options: Array<{ label: string, value: string | number }>
  placeholder?: string
}>()
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref<HTMLElement | null>(null)

function selectOption(option: { label: string, value: string | number }) {
  emit('update:modelValue', option.value)
  open.value = false
  // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
  window.dispatchEvent(new CustomEvent('table-changed'));
}

function handleClickOutside(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})

watch(open, (val) => {
  if (!val) return
  setTimeout(() => {
    const selected = root.value?.querySelector('.custom-option.selected') as HTMLElement
    if (selected) selected.scrollIntoView({ block: 'nearest' })
  }, 0)
})
</script>

<template>
  <div ref="root" class="relative" style="display: inline-block; min-width: 8ch;">
    <button
      class="custom-select-filter-btn border border-gray-300 rounded-md text-left bg-white focus:outline-none focus:ring-2 focus:ring-[#5856D6] transition-all duration-300"
      style="position: relative;"
      @click="open = !open"
      type="button"
      :style="{}"
    >
      <span :class="{ 'text-gray-900': (modelValue ?? '') !== '', 'text-gray-400': (modelValue ?? '') === '' }" style="margin-right: 2.25rem;">
        {{ options.find(o => o.value === (modelValue ?? ''))?.label || placeholder || 'Pilih...' }}
      </span>
      <span class="custom-select-arrow">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="1rem" height="1rem" class="text-gray-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
      </span>
    </button>
    <div
      v-if="open"
      class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg"
    >
      <ul>
        <li
          v-for="(option, idx) in options"
          :key="option.value"
          @click="selectOption(option)"
          class="custom-option px-4 py-2 cursor-pointer text-sm border-b border-dotted border-gray-300 hover:bg-[#5D42FF14] hover:text-[#644DED]"
          :class="{
            'rounded-t-lg': idx === 0,
            'rounded-b-lg border-b-0': idx === options.length - 1,
            'bg-[#EFF6F9] text-[#333] selected': (modelValue ?? '') === option.value
          }"
        >
          {{ option.label }}
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.custom-select-filter-btn {
  min-width: 8ch;
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.2s;
  position: relative;
}
.custom-select-arrow {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}
.custom-option {
  transition: background 0.2s, color 0.2s;
}
</style>
