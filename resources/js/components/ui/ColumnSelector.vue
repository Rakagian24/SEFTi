<script setup lang="ts">
import { ref, watch } from 'vue';

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const dropdown = ref<HTMLElement>();

const props = defineProps<{
  columns: Column[];
  modelValue: Column[];
}>();

const emit = defineEmits<{
  'update:modelValue': [value: Column[]];
}>();

const localColumns = ref<Column[]>([]);

// Initialize local columns
watch(() => props.columns, (newColumns) => {
  localColumns.value = newColumns.map(col => ({ ...col }));
}, { immediate: true });

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localColumns.value = newValue.map(col => ({ ...col }));
}, { immediate: true });

function toggleColumn(key: string) {
  const column = localColumns.value.find(col => col.key === key);
  if (column) {
    column.checked = !column.checked;
    emit('update:modelValue', localColumns.value);
  }
}

function selectAll() {
  localColumns.value.forEach(col => {
    col.checked = true;
  });
  emit('update:modelValue', localColumns.value);
}

function deselectAll() {
  localColumns.value.forEach(col => {
    col.checked = false;
  });
  emit('update:modelValue', localColumns.value);
}

function resetToDefault() {
  const defaultColumns = ['no_bm', 'customer','tanggal', 'department', 'bank_account', 'currency', 'nilai'];
  localColumns.value.forEach(col => {
    col.checked = defaultColumns.includes(col.key);
  });
  emit('update:modelValue', localColumns.value);
}
</script>

<template>
  <div class="relative">
    <!-- Trigger Button -->
    <button
      @click="dropdown?.classList.toggle('hidden')"
      class="flex items-center gap-2 px-3 py-2 text-sm bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] transition-all duration-300 h-[38px]"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
      </svg>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <div
      ref="dropdown"
      class="hidden absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-md shadow-lg z-50"
      @click.outside="dropdown?.classList.add('hidden')"
    >
      <div class="p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-medium text-gray-900">Pilih Kolom</h3>
          <div class="flex gap-1">
            <button
              @click="selectAll"
              class="text-xs px-2 py-1 text-blue-600 hover:text-blue-800"
            >
              Semua
            </button>
            <button
              @click="deselectAll"
              class="text-xs px-2 py-1 text-gray-600 hover:text-gray-800"
            >
              Kosong
            </button>
            <button
              @click="resetToDefault"
              class="text-xs px-2 py-1 text-green-600 hover:text-green-800"
            >
              Default
            </button>
          </div>
        </div>

        <div class="space-y-2 max-h-64 overflow-y-auto">
          <label
            v-for="column in localColumns"
            :key="column.key"
            class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer"
          >
            <input
              type="checkbox"
              :checked="column.checked"
              @change="toggleColumn(column.key)"
              class="rounded border-gray-300 text-[#5856D6] focus:ring-[#5856D6]"
            />
            <span class="text-sm text-gray-700">{{ column.label }}</span>
            <span v-if="column.sortable" class="text-xs text-gray-400">(Sortable)</span>
          </label>
        </div>

        <div class="mt-3 pt-3 border-t border-gray-200">
          <p class="text-xs text-gray-500">
            {{ localColumns.filter(col => col.checked).length }} dari {{ localColumns.length }} kolom dipilih
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
