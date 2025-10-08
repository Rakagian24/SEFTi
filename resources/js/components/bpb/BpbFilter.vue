<script setup lang="ts">
import { ref } from "vue";
import CustomSelectFilter from "@/components/ui/CustomSelectFilter.vue";

const emit = defineEmits<{
  filter: [payload: any];
  reset: [];
}>();

const form = ref({
  tanggal_start: "",
  tanggal_end: "",
  no_bpb: "",
  department_id: "",
  status: "",
  supplier_id: "",
  per_page: "10",
  search: "",
});

function apply() {
  emit("filter", { ...form.value });
}

function reset() {
  form.value = {
    tanggal_start: "",
    tanggal_end: "",
    no_bpb: "",
    department_id: "",
    status: "",
    supplier_id: "",
    per_page: "10",
    search: "",
  };
  emit("reset");
}
</script>

<template>
  <div class="bg-white rounded-lg border shadow-sm p-4">
    <div class="flex gap-3 flex-wrap items-end">
      <div class="space-y-1">
        <label class="text-xs text-gray-500">Tanggal Mulai</label>
        <input
          v-model="form.tanggal_start"
          type="date"
          class="border rounded px-2 py-1"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs text-gray-500">Tanggal Akhir</label>
        <input v-model="form.tanggal_end" type="date" class="border rounded px-2 py-1" />
      </div>
      <div class="space-y-1">
        <label class="text-xs text-gray-500">No. BPB</label>
        <input
          v-model="form.no_bpb"
          type="text"
          placeholder="Enter No. BPB"
          class="border rounded px-2 py-1"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs text-gray-500">Departemen</label>
        <input
          v-model="form.department_id"
          type="number"
          class="border rounded px-2 py-1 w-36"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs text-gray-500">Status</label>
        <CustomSelectFilter
          v-model="form.status"
          :options="[
            { label: 'Semua', value: '' },
            { label: 'Draft', value: 'Draft' },
            { label: 'In Progress', value: 'In Progress' },
            { label: 'Approved', value: 'Approved' },
            { label: 'Rejected', value: 'Rejected' },
            { label: 'Canceled', value: 'Canceled' },
          ]"
          width="10rem"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs text-gray-500">Supplier</label>
        <input
          v-model="form.supplier_id"
          type="number"
          class="border rounded px-2 py-1 w-36"
        />
      </div>
      <div class="space-y-1">
        <label class="text-xs text-gray-500">Show</label>
        <CustomSelectFilter
          v-model="form.per_page"
          :options="[
            { label: '10', value: '10' },
            { label: '25', value: '25' },
            { label: '50', value: '50' },
            { label: '100', value: '100' },
          ]"
          width="6rem"
        />
      </div>
      <div class="flex-1 space-y-1 min-w-64">
        <label class="text-xs text-gray-500">Search</label>
        <input
          v-model="form.search"
          type="text"
          class="border rounded px-2 py-1 w-full"
          placeholder="Cari..."
        />
      </div>
      <div class="ml-auto flex gap-2">
        <button class="px-3 py-2 rounded border" @click="reset">Reset</button>
        <button class="px-3 py-2 rounded bg-[#5856D6] text-white" @click="apply">
          Filter
        </button>
      </div>
    </div>
  </div>
</template>
