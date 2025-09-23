<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap justify-between">
        <!-- KIRI: Filter Button & Dropdown -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <!-- Filter Dropdowns (expand upwards) -->
          <div
            v-if="showFilters"
            class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4"
          >
            <!-- Tanggal Range Filter -->
            <div class="flex-shrink-0">
              <DateRangeFilter
                :start="form.tanggal_start"
                :end="form.tanggal_end"
                @update:start="
                  (val) => {
                    form.tanggal_start = val;
                    applyFilters();
                  }
                "
                @update:end="
                  (val) => {
                    form.tanggal_end = val;
                    applyFilters();
                  }
                "
              />
            </div>
            <!-- Department Filter -->
            <div v-if="(departments || []).length > 1" class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="form.department_id"
                @update:modelValue="
                  (val) => {
                    form.department_id = val;
                    applyFilters();
                  }
                "
                :options="[
                  { label: 'Semua Department', value: '' },
                  ...(departments || []).map((d) => ({
                    label: d.name,
                    value: d.id,
                  })),
                ]"
                placeholder="Department"
              />
            </div>
            <!-- Status Filter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="form.status"
                @update:modelValue="
                  (val) => {
                    form.status = val;
                    applyFilters();
                  }
                "
                :options="[
                  { label: 'Semua Status', value: '' },
                  { label: 'In Progress', value: 'In Progress' },
                  { label: 'Verified', value: 'Verified' },
                  { label: 'Validated', value: 'Validated' },
                  { label: 'Approved', value: 'Approved' },
                  { label: 'Rejected', value: 'Rejected' },
                ]"
                placeholder="Status"
              />
            </div>
            <!-- Metode Pembayaran Filter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="form.metode_pembayaran"
                @update:modelValue="
                  (val) => {
                    form.metode_pembayaran = val;
                    applyFilters();
                  }
                "
                :options="[
                  { label: 'Semua Metode', value: '' },
                  { label: 'Transfer', value: 'Transfer' },
                  { label: 'Cek/Giro', value: 'Cek/Giro' },
                  { label: 'Kredit', value: 'Kredit' },
                ]"
                placeholder="Metode Pembayaran"
                style="min-width: 14rem"
              />
            </div>
            <!-- Reset Icon Button -->
            <button
              @click="resetFilters"
              class="flex-shrink-0 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors duration-150 mt-1"
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
                  d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 48.108 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                />
              </svg>
            </button>
          </div>
          <!-- Filter Button -->
          <div
            class="flex items-center cursor-pointer select-none"
            @click="toggleFilters"
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
                d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"
              />
            </svg>
            <span
              :class="
                'inline-block transition-transform duration-300 ml-2 ' +
                (showFilters ? 'rotate-45' : 'rotate-0')
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
        <!-- KANAN: Show entries & Search (align bottom) -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0 mt-4">
          <!-- Show entries per page -->
          <div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <CustomSelectFilter
              :model-value="form.entriesPerPage"
              @update:modelValue="
                (value) => {
                  form.entriesPerPage = value;
                  emit('update:entries-per-page', value);
                  applyFilters();
                }
              "
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
              v-model="form.search"
              @input="debouncedSearch"
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
import { ref, watch } from "vue";
import CustomSelectFilter from "../ui/CustomSelectFilter.vue";
import DateRangeFilter from "../ui/DateRangeFilter.vue";
import ColumnSelector from "../ui/ColumnSelector.vue";

// Props
const props = defineProps<{
  filters: any;
  departments: Array<{ id: number; name: string }>;
  entriesPerPage: number;
  columns?: any[];
}>();

const emit = defineEmits<{
  filter: [payload: Record<string, any>];
  reset: [];
  "update:entries-per-page": [perPage: number];
  "update:columns": [columns: any[]];
}>();

const form = ref({
  tanggal_start: "",
  tanggal_end: "",
  department_id: "",
  status: "",
  metode_pembayaran: "",
  search: "",
  entriesPerPage: props.entriesPerPage || 10,
});
const showFilters = ref(false);

const localColumns = ref<any[]>(
  (props.columns as any[]) || [
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

// Watch for prop changes
watch(
  () => props.filters,
  (val) => {
    if (val) {
      form.value = {
        tanggal_start: val.tanggal_start || "",
        tanggal_end: val.tanggal_end || "",
        department_id: val.department_id || "",
        status: val.status || "",
        metode_pembayaran: val.metode_pembayaran || "",
        search: val.search || "",
        entriesPerPage: val.per_page || 10,
      };
    }
  },
  { immediate: true }
);

watch(
  () => props.entriesPerPage,
  (val) => {
    form.value.entriesPerPage = val || 10;
  }
);

watch(
  () => props.columns,
  (val) => {
    if (val) {
      localColumns.value = val as any[];
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

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

function toggleFilters() {
  showFilters.value = !showFilters.value;
}

function applyFilters() {
  const selectedColumnKeys = localColumns.value
    .filter((col) => col.checked)
    .map((col) => col.key);

  emit("filter", {
    tanggal_start: form.value.tanggal_start,
    tanggal_end: form.value.tanggal_end,
    department_id: form.value.department_id,
    status: form.value.status,
    metode_pembayaran: form.value.metode_pembayaran,
    search: form.value.search,
    per_page: form.value.entriesPerPage,
    search_columns: selectedColumnKeys.join(","),
  });
}

function resetFilters() {
  form.value = {
    tanggal_start: "",
    tanggal_end: "",
    department_id: "",
    status: "",
    metode_pembayaran: "",
    search: "",
    entriesPerPage: 10,
  };
  applyFilters();
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
</style>
