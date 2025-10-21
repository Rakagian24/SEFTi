<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex gap-4 flex-wrap justify-between">
        <!-- KIRI: Filter Button & Dropdown -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <Transition name="filter-expand">
            <div v-if="showFilters" class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4">
              <!-- Tanggal Range -->
              <div class="flex-shrink-0">
                <DateRangeFilter
                  :start="tanggal_start"
                  :end="tanggal_end"
                  @update:start="(val) => { tanggal_start = val; emitFilter(); }"
                  @update:end="(val) => { tanggal_end = val; emitFilter(); }"
                />
              </div>

              <!-- No PO Anggaran -->
              <div class="flex-shrink-0">
                <input
                  v-model="no_po_anggaran"
                  @input="emitFilter"
                  type="text"
                  placeholder="No. PO Anggaran"
                  class="w-48 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
                />
              </div>

              <!-- Department -->
              <div v-if="(departments || []).length !== 1" class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="department_id"
                  @update:modelValue="(val) => { department_id = val; emitFilter(); }"
                  :options="[
                    { label: 'Semua Departemen', value: '' },
                    ...(departments || []).map((d) => ({ label: d.nama_department || d.name, value: d.id })),
                  ]"
                  placeholder="Departemen"
                />
              </div>

              <!-- Status -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="status"
                  @update:modelValue="(val) => { status = val; emitFilter(); }"
                  :options="[
                    { label: 'Semua Status', value: '' },
                    { label: 'Draft', value: 'Draft' },
                    { label: 'In Progress', value: 'In Progress' },
                    { label: 'Approved', value: 'Approved' },
                    { label: 'Canceled', value: 'Canceled' },
                    { label: 'Rejected', value: 'Rejected' },
                  ]"
                  placeholder="Status"
                />
              </div>

              <!-- Reset Icon Button -->
              <button
                @click="resetFilter"
                class="flex-shrink-0 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors duration-150 mt-1"
                title="Reset filter"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 48.108 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
              </button>
            </div>
          </Transition>

          <!-- Filter Toggle -->
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
        </div>

        <!-- KANAN: Show entries & Search & Column selector -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0">
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
                { label: '100', value: 100 },
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
              placeholder="Search..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <!-- Column Selector -->
          <div class="flex-shrink-0">
            <ColumnSelector :columns="localColumns" v-model="localColumns" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import DateRangeFilter from "../ui/DateRangeFilter.vue";
import CustomSelectFilter from "../ui/CustomSelectFilter.vue";
import ColumnSelector from "../ui/ColumnSelector.vue";

interface Column { key: string; label: string; checked: boolean; sortable?: boolean }

const props = defineProps<{ filters: Record<string, any>; departments: any[]; columns?: Column[]; entriesPerPage?: number }>();
const emit = defineEmits(["filter", "reset", "update:entriesPerPage", "update:columns"]);

const tanggal_start = ref("");
const tanggal_end = ref("");
const no_po_anggaran = ref("");
const department_id = ref("");
const status = ref("");
const searchTerm = ref("");
const entriesPerPage = ref(props.entriesPerPage || 10);
const showFilters = ref(false);

const localColumns = ref<Column[]>(
  (props.columns as Column[]) || [
    { key: 'no_po_anggaran', label: 'No. PO Anggaran', checked: true, sortable: true },
    { key: 'tanggal', label: 'Tanggal', checked: true, sortable: true },
    { key: 'department', label: 'Departemen', checked: true },
    { key: 'nominal', label: 'Nominal', checked: true, sortable: true },
    { key: 'status', label: 'Status', checked: true, sortable: true },
  ]
);

watch(() => props.filters, (val) => {
  tanggal_start.value = val.tanggal_start || "";
  tanggal_end.value = val.tanggal_end || "";
  no_po_anggaran.value = val.no_po_anggaran || "";
  department_id.value = val.department_id || "";
  status.value = val.status || "";
  searchTerm.value = val.search || "";
}, { immediate: true });

watch(() => props.entriesPerPage, (val) => { entriesPerPage.value = val || 10; });
watch(() => props.columns, (val) => { if (val) localColumns.value = val as Column[]; });

watch(localColumns, (newColumns) => { emit("update:columns", newColumns); }, { deep: true });

function toggleFilters() { showFilters.value = !showFilters.value; }

function emitFilter() {
  const payload = {
    tanggal_start: tanggal_start.value,
    tanggal_end: tanggal_end.value,
    no_po_anggaran: no_po_anggaran.value,
    department_id: department_id.value,
    status: status.value,
    search: searchTerm.value,
    entriesPerPage: entriesPerPage.value,
  };
  emit("filter", payload);
  emit("update:entriesPerPage", entriesPerPage.value);
}

function resetFilter() {
  tanggal_start.value = "";
  tanggal_end.value = "";
  no_po_anggaran.value = "";
  department_id.value = "";
  status.value = "";
  searchTerm.value = "";
  entriesPerPage.value = 10;
  emitFilter();
  emit("reset");
}
</script>

<style scoped>
.rotate-45 { transform: rotate(45deg); }
.rotate-0 { transform: rotate(0); }

.filter-expand-enter-active,
.filter-expand-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; }
.filter-expand-enter-from,
.filter-expand-leave-to { opacity: 0; max-height: 0; margin-bottom: 0; padding-bottom: 0; }
.filter-expand-enter-to,
.filter-expand-leave-from { opacity: 1; max-height: 200px; margin-bottom: 0.75rem; padding-bottom: 1rem; }
</style>
