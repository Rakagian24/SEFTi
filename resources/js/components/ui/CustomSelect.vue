<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'

const props = defineProps<{
  modelValue?: string | number
  options: Array<{ label: string, value: string | number }>
  placeholder?: string
  label?: string
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

const isFloating = computed(() => {
  return open.value || (props.modelValue !== undefined && props.modelValue !== null && props.modelValue !== '')
})
</script>

<template>
  <div ref="root" class="relative w-full floating-input">
    <button
      class="floating-input-field w-full text-left cursor-pointer"
      @click="open = !open"
      type="button"
      :style="{}"
    >
      <span :class="{ 'text-gray-900': (modelValue ?? '') !== '', 'text-gray-400': (modelValue ?? '') === '' }">
        <!-- Tampilkan label option jika sudah dipilih, kosongkan jika belum -->
        <template v-if="(modelValue ?? '') !== ''">
          {{ options.find(o => o.value.toString() === (modelValue ?? '').toString())?.label }}
        </template>
        <!-- Tampilkan placeholder hanya saat dropdown dibuka dan belum memilih -->
        <template v-else-if="open">
          {{ placeholder || 'Pilih...' }}
        </template>
        <!-- Jika belum memilih dan dropdown belum dibuka, kosong -->
        <template v-else>
          &nbsp;
        </template>
      </span>
      <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">&#9662;</span>
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
      <ul>
        <li
          v-for="(option, idx) in options"
          :key="option.value"
          @click="selectOption(option)"
          class="custom-option px-4 py-2 cursor-pointer text-sm border-b border-dotted border-gray-300 hover:bg-[#5D42FF14] hover:text-[#644DED]"
          :class="{
            'rounded-t-lg': idx === 0,
            'rounded-b-lg border-b-0': idx === options.length - 1,
            'bg-[#EFF6F9] text-[#333] selected': (modelValue ?? '').toString() === option.value.toString()
          }"
        >
          {{ option.label }}
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
}
button.floating-input-field:focus {
  outline: none;
  border-color: #1F9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
</style>
