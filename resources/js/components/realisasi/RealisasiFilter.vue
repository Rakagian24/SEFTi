<template>
  <div class="mb-4 bg-white rounded-xl p-4 shadow">
    <div class="flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-xs text-gray-500 mb-1">No. Realisasi</label>
        <input v-model="local.no_realisasi" class="input input-bordered input-sm w-48" />
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Departemen</label>
        <select v-model="local.department_id" class="select select-bordered select-sm w-56">
          <option :value="''">Semua</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Status</label>
        <select v-model="local.status" class="select select-bordered select-sm w-48">
          <option :value="''">Semua</option>
          <option value="Draft">Draft</option>
          <option value="In Progress">In Progress</option>
          <option value="Rejected">Rejected</option>
          <option value="Approved">Approved</option>
          <option value="Canceled">Canceled</option>
        </select>
      </div>
      <div class="ml-auto flex gap-2">
        <button @click="$emit('filter', local)" class="btn btn-sm btn-primary">Filter</button>
        <button @click="reset" class="btn btn-sm">Reset</button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { reactive } from 'vue';
const props = defineProps<{ filters: Record<string, any>; departments: any[]; columns: any[]; entriesPerPage: number }>();
const emit = defineEmits(['filter','reset','update:columns','update:entries-per-page']);
const local = reactive({ ...props.filters });
function reset() { Object.keys(local).forEach(k => (local as any)[k] = ''); emit('reset'); }
</script>
