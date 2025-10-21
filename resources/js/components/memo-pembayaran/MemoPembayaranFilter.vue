<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200 relative z-50">
    <div class="px-6 py-4">
      <div class="flex gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter Button & Dropdown -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <!-- Filter Dropdowns (when expanded) - POSITIONED ABOVE -->
          <Transition name="filter-expand">
            <div
              v-if="isFilterOpen"
              class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4"
            >
              <!-- Date Range Filter -->
              <div class="flex-shrink-0">
                <DateRangeFilter
                  :start="form.tanggal_start"
                  :end="form.tanggal_end"
                  @update:start="
                    (val) => {
                      form.tanggal_start = val;
                      applyFilter();
                    }
                  "
                  @update:end="
                    (val) => {
                      form.tanggal_end = val;
                      applyFilter();
                    }
                  "
                />
              </div>

              <!-- Department Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.department_id"
                  :options="departmentOptions"
                  placeholder="Departemen"
                  style="min-width: 12rem"
                />
              </div>

              <!-- Status Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.status"
                  :options="statusFilterOptions"
                  placeholder="Status"
                  style="min-width: 10rem"
                />
              </div>

              <!-- Metode Pembayaran Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.metode_pembayaran"
                  :options="metodePembayaranFilterOptions"
                  placeholder="Metode Pembayaran"
                  style="min-width: 14rem"
                />
              </div>

              <!-- Supplier Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.supplier_id"
                  :options="supplierOptions"
                  placeholder="Supplier"
                  :searchable="true"
                  style="min-width: 12rem"
                />
              </div>

              <!-- Reset Icon Button -->
              <button
                @click="resetFilter"
                class="flex-shrink-0 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors duration-150"
                title="Reset filter"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="size-6"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                  />
                </svg>
              </button>
            </div>
          </Transition>

          <!-- Filter Button -->
          <div class="flex items-center cursor-pointer select-none" @click="toggleFilter">
            <!-- Funnel Icon -->
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="size-6"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"
              />
            </svg>

            <!-- Plus Icon with Animation -->
            <span
              :class="
                'inline-block transition-transform duration-300 ml-2 ' +
                (isFilterOpen ? 'rotate-45' : 'rotate-0')
              "
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="w-4 h-4 text-gray-600"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 4.5v15m7.5-7.5h-15"
                />
              </svg>
            </span>

            <span class="ml-2 text-gray-700 text-sm font-medium">Filter</span>
          </div>
        </div>

        <!-- RIGHT: Show entries & Search -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0 mt-4">
          <!-- Show entries per page -->
          <div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <div class="relative">
              <CustomSelectFilter
                v-model="form.entriesPerPage"
                :options="[
                  { label: '10', value: '10' },
                  { label: '25', value: '25' },
                  { label: '50', value: '50' },
                  { label: '100', value: '100' },
                ]"
                width="5.5rem"
                @update:modelValue="applyFilter"
              />
            </div>
            <span class="ml-2">entries</span>
          </div>

          <!-- Search -->
          <div class="relative flex-1 min-w-64">
            <input
              v-model="form.search"
              @input="debouncedApplyFilter"
              type="text"
              placeholder="Cari berdasarkan kolom yang ditampilkan..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
            />
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
              <svg
                class="h-4 w-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
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
import { ref, computed, watch } from "vue";
import axios from "axios";
import DateRangeFilter from "../ui/DateRangeFilter.vue";
import CustomSelectFilter from "../ui/CustomSelectFilter.vue";
import ColumnSelector from "../ui/ColumnSelector.vue";

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const props = defineProps<{
  filters: Record<string, any>;
  departments: any[];
  statusOptions: string[];
  metodePembayaranOptions: string[];
  columns?: Column[];
}>();

const emit = defineEmits<{
  filter: [payload: Record<string, any>];
  reset: [];
  "update:columns": [columns: Column[]];
}>();

const isFilterOpen = ref(false);
let debounceTimer: number | null = null;

const form = ref({
  tanggal_start: "",
  tanggal_end: "",
  no_mb: "",
  department_id: "",
  status: "",
  metode_pembayaran: "",
  supplier_id: "",
  search: "",
  entriesPerPage: "10",
});

// Column configuration - Default columns for regular memo pembayaran view
const localColumns = ref<Column[]>(
  (props.columns as Column[]) || [
    { key: "no_mb", label: "No. MB", checked: true, sortable: false },
    { key: "no_po", label: "No. PO", checked: true, sortable: false },
    { key: "supplier", label: "Supplier", checked: true, sortable: false },
    { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
    { key: "status", label: "Status", checked: true, sortable: true },
    { key: "perihal", label: "Perihal", checked: false, sortable: false },
    { key: "department", label: "Department", checked: false, sortable: false },
    {
      key: "metode_pembayaran",
      label: "Metode Pembayaran",
      checked: false,
      sortable: false,
    },
    { key: "grand_total", label: "Grand Total", checked: false, sortable: true },
    { key: "nama_rekening", label: "Nama Rekening", checked: false, sortable: false },
    { key: "no_rekening", label: "No. Rekening", checked: false, sortable: false },
    {
      key: "no_kartu_kredit",
      label: "No. Kartu Kredit",
      checked: false,
      sortable: false,
    },
    { key: "no_giro", label: "No. Giro", checked: false, sortable: false },
    { key: "tanggal_giro", label: "Tanggal Giro", checked: false, sortable: true },
    { key: "tanggal_cair", label: "Tanggal Cair", checked: false, sortable: true },
    { key: "keterangan", label: "Keterangan", checked: false, sortable: false },
    { key: "total", label: "Total", checked: false, sortable: true },
    { key: "diskon", label: "Diskon", checked: false, sortable: true },
    { key: "ppn", label: "PPN", checked: false, sortable: false },
    { key: "ppn_nominal", label: "PPN Nominal", checked: false, sortable: true },
    { key: "pph_nominal", label: "PPH Nominal", checked: false, sortable: true },
    { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
    { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
  ]
);

// Initialize form with existing filters
watch(
  () => props.filters,
  (newFilters) => {
    if (newFilters) {
      form.value = {
        tanggal_start: newFilters.tanggal_start || "",
        tanggal_end: newFilters.tanggal_end || "",
        no_mb: newFilters.no_mb || "",
        department_id: newFilters.department_id || "",
        status: newFilters.status || "",
        metode_pembayaran: newFilters.metode_pembayaran || "",
        supplier_id: newFilters.supplier_id || "",
        search: newFilters.search || "",
        entriesPerPage: newFilters.per_page || "10",
      };
    }
  },
  { immediate: true }
);

// Watch individual filter changes and apply immediately
watch(
  () => form.value.tanggal_start,
  () => {
    if (form.value.tanggal_start !== undefined) applyFilter();
  }
);
watch(
  () => form.value.tanggal_end,
  () => {
    if (form.value.tanggal_end !== undefined) applyFilter();
  }
);
watch(() => form.value.no_mb, debouncedApplyFilter);
watch(
  () => form.value.department_id,
  () => {
    if (form.value.department_id !== undefined) applyFilter();
  }
);
watch(
  () => form.value.status,
  () => {
    if (form.value.status !== undefined) applyFilter();
  }
);
watch(
  () => form.value.metode_pembayaran,
  () => {
    if (form.value.metode_pembayaran !== undefined) applyFilter();
  }
);
watch(
  () => form.value.supplier_id,
  () => {
    if (form.value.supplier_id !== undefined) applyFilter();
  }
);

watch(
  () => form.value.entriesPerPage,
  () => {
    if (form.value.entriesPerPage !== undefined) applyFilter();
  }
);

// Watch columns changes
watch(
  () => props.columns,
  (val) => {
    if (val) {
      localColumns.value = val as Column[];
    }
  }
);

watch(
  localColumns,
  (newColumns) => {
    emit("update:columns", newColumns);
  },
  { deep: true }
);

const departmentOptions = computed(() => {
  return [
    { label: "Semua Departemen", value: "" },
    ...props.departments.map((dept) => ({
      label: dept.name,
      value: dept.id.toString(),
    })),
  ];
});

const statusFilterOptions = computed(() => {
  const statusList = [
    "Draft",
    "In Progress",
    "Verified",
    "Validated",
    "Approved",
    "Canceled",
    "Rejected",
  ];
  return [
    { label: "Semua Status", value: "" },
    ...statusList.map((status) => ({
      label: status,
      value: status,
    })),
  ];
});

const metodePembayaranFilterOptions = computed(() => {
  const metodeList = ["Transfer", //"Cek/Giro",
    "Kredit"];
  return [
    { label: "Semua Metode", value: "" },
    ...metodeList.map((metode) => ({
      label: metode,
      value: metode,
    })),
  ];
});

function toggleFilter() {
  isFilterOpen.value = !isFilterOpen.value;
  localStorage.setItem(
    "memoPembayaranShowFilters",
    isFilterOpen.value ? "true" : "false"
  );
}

function debouncedApplyFilter() {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  debounceTimer = setTimeout(() => {
    applyFilter();
  }, 300);
}

function applyFilter() {
  const payload: Record<string, any> = {};

  if (form.value.tanggal_start) {
    payload.tanggal_start = form.value.tanggal_start;
  }
  if (form.value.tanggal_end) {
    payload.tanggal_end = form.value.tanggal_end;
  }
  if (form.value.no_mb) payload.no_mb = form.value.no_mb;
  if (form.value.department_id) payload.department_id = form.value.department_id;
  if (form.value.status) payload.status = form.value.status;
  if (form.value.metode_pembayaran)
    payload.metode_pembayaran = form.value.metode_pembayaran;
  if (form.value.supplier_id) payload.supplier_id = form.value.supplier_id;

  // Only include search if it has actual content (not empty or just whitespace)
  if (form.value.search && form.value.search.trim()) {
    payload.search = form.value.search.trim();
  }

  if (form.value.entriesPerPage) payload.per_page = form.value.entriesPerPage;

  // Add search columns for dynamic search
  const selectedColumnKeys = localColumns.value
    .filter((col) => col.checked)
    .map((col) => col.key);

  if (selectedColumnKeys.length > 0) {
    payload.search_columns = selectedColumnKeys.join(",");
  }

  emit("filter", payload);
  window.dispatchEvent(new CustomEvent("content-changed"));
}

function resetFilter() {
  form.value = {
    tanggal_start: "",
    tanggal_end: "",
    no_mb: "",
    department_id: "",
    supplier_id: "",
    status: "",
    metode_pembayaran: "",
    search: "",
    entriesPerPage: "10",
  };
  emit("reset");
  window.dispatchEvent(new CustomEvent("content-changed"));
}

// Initialize filter state from localStorage
const savedFilterState = localStorage.getItem("memoPembayaranShowFilters");
if (savedFilterState !== null) {
  isFilterOpen.value = savedFilterState === "true";
}

// Supplier options (prefetch list for dropdown)
const supplierOptions = ref<Array<{ label: string; value: string }>>([
  { label: "Semua Supplier", value: "" },
]);

async function fetchSuppliers() {
  try {
    const { data } = await axios.get("/memo-pembayaran/suppliers/options", {
      params: { per_page: 200 },
      withCredentials: true,
    });
    const list = Array.isArray(data?.data) ? data.data : [];
    const opts = list.map((s: any) => ({
      label: s.nama_supplier,
      value: String(s.id),
    }));
    supplierOptions.value = [{ label: "Semua Supplier", value: "" }, ...opts];
  } catch {
    supplierOptions.value = [{ label: "Semua Supplier", value: "" }];
  }
}

fetchSuppliers();
</script>

<style scoped>
/* Animasi untuk ikon plus */
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
}

/* Smooth transition untuk filter dropdown */
.filter-dropdown-enter-active,
.filter-dropdown-leave-active {
  transition: all 0.3s ease;
}
.filter-dropdown-enter-from,
.filter-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Animasi untuk expand ke atas */
.filter-expand-enter-active,
.filter-expand-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}
.filter-expand-enter-from,
.filter-expand-leave-to {
  opacity: 0;
  max-height: 0;
  margin-bottom: 0;
  padding-bottom: 0;
}
.filter-expand-enter-to,
.filter-expand-leave-from {
  opacity: 1;
  max-height: 300px;
  margin-bottom: 0.75rem;
  padding-bottom: 1rem;
}

/* Responsive filter layout */
@media (max-width: 768px) {
  .filter-dropdowns {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-dropdowns > div {
    width: 100%;
  }
}

/* Custom style for inputs to match the design */
input[type="text"] {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: #fff;
  transition: box-shadow 0.2s, border-color 0.2s;
}

input[type="text"]:focus {
  border-color: #5856d6;
  box-shadow: 0 0 0 2px rgba(88, 86, 214, 0.2);
}

/* Date range filter styling now comes from DateRangeFilter.vue */
</style>
