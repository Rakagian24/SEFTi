<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'

const props = defineProps<{
  modelValue?: string | number
  options: Array<{ label: string, value: string | number }>
  placeholder?: string
  label?: string
  loading?: boolean
  searchable?: boolean
  disabled?: boolean
}>()

const emit = defineEmits(['update:modelValue', 'search'])

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const searchQuery = ref('')

function selectOption(option: { label: string, value: string | number }) {
  emit('update:modelValue', option.value)
  open.value = false
  searchQuery.value = ''
  // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
  window.dispatchEvent(new CustomEvent('table-changed'));
}

function handleClickOutside(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false
    searchQuery.value = ''
  }
}

function handleSearch(event: Event) {
  const target = event.target as HTMLInputElement
  searchQuery.value = target.value
  if (props.searchable) {
    emit('search', target.value)
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})

watch(open, (val) => {
  if (!val) {
    searchQuery.value = ''
    return
  }
  setTimeout(() => {
    const selected = root.value?.querySelector('.custom-option.selected') as HTMLElement
    if (selected) selected.scrollIntoView({ block: 'nearest' })
  }, 0)
})

const isFloating = computed(() => {
  return open.value || (props.modelValue !== undefined && props.modelValue !== null && props.modelValue !== '')
})

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) return props.options
  return props.options.filter(option =>
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})
</script>

<template>
  <div ref="root" class="relative w-full floating-input">
    <button
      class="floating-input-field w-full text-left"
      :class="{ 'cursor-pointer': !disabled, 'cursor-not-allowed opacity-50': disabled }"
      @click="!disabled && (open = !open)"
      type="button"
      :disabled="disabled"
      :style="{}"
    >
      <span :class="{ 'text-gray-900': (modelValue ?? '') !== '', 'text-gray-400': (modelValue ?? '') === '' }">
        <!-- Tampilkan label option jika sudah dipilih -->
        <template v-if="(modelValue ?? '') !== ''">
          {{ options.find(o => o.value.toString() === (modelValue ?? '').toString())?.label }}
        </template>
        <!-- Tampilkan placeholder jika belum memilih -->
        <template v-else>
          {{ placeholder || 'Pilih...' }}
        </template>
      </span>
      <svg
        class="pointer-events-none w-4 h-4 text-gray-400 flex-shrink-0"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        :class="{ 'rotate-180': open }"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
    <label
      v-if="$slots.label"
      class="floating-label"
      :class="{ 'floating': isFloating }"
      @click="open = true"
      style="left: 0.75rem;"
    >
      <slot name="label" />
    </label>
    <div
      v-if="open"
      class="absolute z-[9999] mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
    >
      <!-- Search input -->
      <div v-if="searchable" class="sticky top-0 bg-white border-b border-gray-200 p-2">
        <input
          v-model="searchQuery"
          @input="handleSearch"
          type="text"
          placeholder="Cari..."
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="p-4 text-center text-gray-500 text-sm">
        Memuat data...
      </div>

      <!-- Options list -->
      <ul v-else>
        <li
          v-for="(option, idx) in filteredOptions"
          :key="option.value"
          @click="selectOption(option)"
          class="custom-option px-4 py-2 cursor-pointer text-sm border-b border-dotted border-gray-300 hover:bg-[#5D42FF14] hover:text-[#644DED]"
          :class="{
            'rounded-t-lg': idx === 0,
            'rounded-b-lg border-b-0': idx === filteredOptions.length - 1,
            'bg-[#EFF6F9] text-[#333] selected': (modelValue ?? '').toString() === option.value.toString()
          }"
        >
          {{ option.label }}
        </li>
        <!-- No results message -->
        <li v-if="filteredOptions.length === 0 && searchQuery" class="px-4 py-2 text-sm text-gray-500 text-center">
          Tidak ada hasil untuk "{{ searchQuery }}"
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.custom-option {
  transition: background 0.2s, color 0.2s;
}
/* Floating label style mirip input */
.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}
.floating-label.floating {
  top: -0.5rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  background-color: white;
  padding: 0 0.25rem;
  pointer-events: auto;
}
.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  min-height: 48px;
  transition: all 0.3s ease-in-out;
  box-sizing: border-box;
}
.floating-input-field:focus {
  outline: none;
  border-color: #1F9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.floating-input {
  position: relative;
  /* margin-top: 1rem; */
}
/* Tambahan agar button.floating-input-field identik dengan input */
button.floating-input-field {
  appearance: none;
  -webkit-appearance: none;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 1rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  min-height: 48px;
  transition: all 0.3s ease-in-out;
  box-sizing: border-box;
  text-align: left;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
button.floating-input-field:focus {
  outline: none;
  border-color: #1F9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
</style>
