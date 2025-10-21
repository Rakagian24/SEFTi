<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
        <input
          v-model="local.search"
          type="text"
          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="No. BPB / No. PO / Supplier"
          @input="emitFilter"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
        <select v-model="local.department_id" class="w-full border-gray-300 rounded-md shadow-sm" @change="emitFilter">
          <option value="">Semua</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select v-model="local.status" class="w-full border-gray-300 rounded-md shadow-sm" @change="emitFilter">
          <option value="">Semua</option>
          <option value="In Progress">In Progress</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Entri per halaman</label>
        <select :value="entriesPerPage" class="w-full border-gray-300 rounded-md shadow-sm" @change="onEntriesChange">
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <div class="mt-4 flex items-center gap-2">
      <button class="px-4 py-2 bg-gray-100 rounded-md border text-gray-700" @click="$emit('reset')">Reset</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';

const props = defineProps<{
  filters: any;
  departments: Array<{ id: number; name: string }>;
  entriesPerPage: number;
}>();

const emit = defineEmits<{
  (e: 'filter', value: any): void;
  (e: 'reset'): void;
  (e: 'update:entries-per-page', value: number): void;
}>();

const local = reactive({
  search: props.filters?.search || '',
  department_id: props.filters?.department_id || '',
  status: props.filters?.status || '',
});

function emitFilter() {
  emit('filter', { ...local });
}

function onEntriesChange(e: Event) {
  const v = parseInt((e.target as HTMLSelectElement).value);
  emit('update:entries-per-page', v);
}

watch(
  () => props.filters,
  (val) => {
    if (!val) return;
    local.search = val.search || '';
    local.department_id = val.department_id || '';
    local.status = val.status || '';
  },
  { deep: true }
);
</script>
