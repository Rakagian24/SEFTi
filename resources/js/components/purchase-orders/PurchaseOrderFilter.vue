<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap justify-between">
        <!-- KIRI: Filter Button & Dropdown -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <!-- Filter Button -->
          <div class="flex items-center cursor-pointer select-none" @click="toggleFilters">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            <span :class="'inline-block transition-transform duration-300 ml-2 ' + (showFilters ? 'rotate-45' : 'rotate-0')">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-gray-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </span>
            <span class="ml-2 text-gray-700 text-sm font-medium">Filter</span>
          </div>
          <!-- Filter Dropdowns (expandable) -->
          <div v-if="showFilters" class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4">
            <!-- Tanggal Range pakai DateRangeFilter -->
            <div class="flex-shrink-0">
              <DateRangeFilter
                :start="tanggal_start"
                :end="tanggal_end"
                @update:start="val => { tanggal_start = val; emitFilter(); }"
                @update:end="val => { tanggal_end = val; emitFilter(); }"
              />
            </div>
            <!-- No. PO Filter -->
            <div class="flex-shrink-0">
              <input type="text" v-model="no_po" @input="emitFilter" placeholder="No. PO" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent min-w-[10rem]" />
            </div>
            <!-- Department Filter pakai CustomSelectFilter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="department"
                @update:modelValue="val => { department = val; emitFilter(); }"
                :options="[{ label: 'Semua Departemen', value: '' }, ...(departments || []).map(d => ({ label: d.nama_department || d.name, value: d.id }))]"
                placeholder="Departemen"
              />
            </div>
            <!-- Status Filter pakai CustomSelectFilter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="status"
                @update:modelValue="val => { status = val; emitFilter(); }"
                :options="[
                  { label: 'Semua Status', value: '' },
                  { label: 'Draft', value: 'Draft' },
                  { label: 'In Progress', value: 'In Progress' },
                  { label: 'Approved', value: 'Approved' },
                  { label: 'Canceled', value: 'Canceled' }
                ]"
                placeholder="Status"
              />
            </div>
            <!-- Perihal Filter pakai CustomSelectFilter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="perihal"
                @update:modelValue="val => { perihal = val; emitFilter(); }"
                :options="[{ label: 'Semua Perihal', value: '' }, ...(perihals || []).map(p => ({ label: p.nama_perihal || p.name, value: p.id }))]"
                placeholder="Perihal"
              />
            </div>
            <!-- Metode Pembayaran Filter -->
            <div class="flex-shrink-0">
              <input type="text" v-model="metode_pembayaran" @input="emitFilter" placeholder="Metode Pembayaran" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent min-w-[12rem]" />
            </div>
            <!-- Reset Icon Button -->
            <button @click="resetFilter" class="flex-shrink-0 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors duration-150 mt-1" title="Reset filter">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
          </div>
        </div>
        <!-- KANAN: Show entries & Search -->
        <div class="flex items-center gap-4 flex-wrap flex-shrink-0">
          <!-- Show entries per page -->
          <div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <CustomSelectFilter
              :model-value="entriesPerPage"
              @update:modelValue="(value) => { entriesPerPage = value; emitFilter(); }"
              :options="[
                { label: '10', value: 10 },
                { label: '25', value: 25 },
                { label: '50', value: 50 },
                { label: '100', value: 100 }
              ]"
              width="5.5rem"
            />
            <span class="ml-2">entries</span>
          </div>
          <!-- Search -->
          <div class="relative flex-1 min-w-64 max-w-xs">
            <input
              v-model="searchTerm"
              @input="emitFilter"
              type="text"
              placeholder="Search Purchase Orders..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import DateRangeFilter from '../ui/DateRangeFilter.vue';
import CustomSelectFilter from '../ui/CustomSelectFilter.vue';

const props = defineProps<{ filters: Record<string, any>, departments: any[], perihals: any[] }>();
const emit = defineEmits(["filter", "reset", "update:entriesPerPage"]);

const tanggal_start = ref("");
const tanggal_end = ref("");
const no_po = ref("");
const department = ref("");
const status = ref("");
const perihal = ref("");
const metode_pembayaran = ref("");
const searchTerm = ref("");
const entriesPerPage = ref(10);
const showFilters = ref(false);

watch(
  () => props.filters,
  (val) => {
    tanggal_start.value = val.tanggal_start || "";
    tanggal_end.value = val.tanggal_end || "";
    no_po.value = val.no_po || "";
    department.value = val.department || "";
    status.value = val.status || "";
    perihal.value = val.perihal || "";
    metode_pembayaran.value = val.metode_pembayaran || "";
  },
  { immediate: true }
);

function toggleFilters() {
  showFilters.value = !showFilters.value;
}

function emitFilter() {
  emit("filter", {
    tanggal_start: tanggal_start.value,
    tanggal_end: tanggal_end.value,
    no_po: no_po.value,
    department: department.value,
    status: status.value,
    perihal: perihal.value,
    metode_pembayaran: metode_pembayaran.value,
    search: searchTerm.value,
    entriesPerPage: entriesPerPage.value,
  });
  emit("update:entriesPerPage", entriesPerPage.value);
}

function resetFilter() {
  tanggal_start.value = "";
  tanggal_end.value = "";
  no_po.value = "";
  department.value = "";
  status.value = "";
  perihal.value = "";
  metode_pembayaran.value = "";
  searchTerm.value = "";
  entriesPerPage.value = 10;
  emitFilter();
  emit("reset");
}
</script>

<style scoped>
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
}

.filter-dropdown-enter-active,
.filter-dropdown-leave-active {
  transition: all 0.3s ease;
}
.filter-dropdown-enter-from,
.filter-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@media (max-width: 768px) {
  .filter-dropdowns {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-dropdowns > div {
    width: 100%;
  }
}

select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: #fff;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding-left: 0.75rem;
  padding-right: 1.25rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  font-size: 0.875rem;
  transition: box-shadow 0.2s, border-color 0.2s;
  outline: none;
}
select:focus {
  border-color: #5856d6;
  box-shadow: 0 0 0 2px rgba(88, 86, 214, 0.2);
}

input[type="text"],
input[type="date"] {
  transition: box-shadow 0.2s, border-color 0.2s;
}

input[type="text"]:focus,
input[type="date"]:focus {
  border-color: #5856d6;
  box-shadow: 0 0 0 2px rgba(88, 86, 214, 0.2);
}
</style>
