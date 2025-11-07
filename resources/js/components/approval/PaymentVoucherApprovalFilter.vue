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
              <div v-if="(departmentOptions || []).length !== 1" class="flex-shrink-0">
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

              <!-- Tipe PV Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.tipe_pv"
                  :options="tipePvFilterOptions"
                  placeholder="Tipe PV"
                  style="min-width: 12rem"
                />
              </div>

              <!-- Metode Pembayaran Filter -->
              <!-- <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.metode_bayar"
                  :options="metodePembayaranFilterOptions"
                  placeholder="Metode Pembayaran"
                  style="min-width: 14rem"
                />
              </div> -->

              <!-- Kelengkapan Dokumen Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  v-model="form.kelengkapan_dokumen"
                  :options="[
                    { label: 'Semua Kelengkapan', value: '' },
                    { label: 'Lengkap', value: '1' },
                    { label: 'Tidak Lengkap', value: '0' },
                  ]"
                  placeholder="Kelengkapan Dokumen"
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
                @click="resetFilters"
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
          <div
            class="flex items-center cursor-pointer select-none"
            @click="toggleFilters"
          >
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
                  { label: '10', value: 10 },
                  { label: '25', value: 25 },
                  { label: '50', value: 50 },
                  { label: '100', value: 100 },
                ]"
                width="5.5rem"
                @update:modelValue="
                  (value) => {
                    form.entriesPerPage = value;
                    emit('update:entries-per-page', value);
                    applyFilters();
                  }
                "
              />
            </div>
            <span class="ml-2">entries</span>
          </div>

          <!-- Search -->
          <div class="relative flex-1 min-w-64">
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
import { ref, watch, computed } from "vue";
import axios from "axios";
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
  tipe_pv: "",
//   metode_bayar: "",
  kelengkapan_dokumen: "",
  supplier_id: "",
  search: "",
  entriesPerPage: props.entriesPerPage || 10,
});
const isFilterOpen = ref(false);

const localColumns = ref<any[]>(
  (props.columns as any[]) || [
  { key: "no_pv", label: "No. PV", checked: true },
  { key: "reference_number", label: "Nomor Referensi Dokumen", checked: true },
  { key: "no_bk", label: "No. BK", checked: true },
  { key: "tanggal", label: "Tanggal", checked: true },
  { key: "status", label: "Status", checked: true },
  { key: "supplier", label: "Supplier", checked: true },
  { key: "department", label: "Departemen", checked: true },
  // Extended columns (unchecked by default)
  { key: "perihal", label: "Perihal", checked: false },
//   { key: "metode_pembayaran", label: "Metode Pembayaran", checked: false },
  { key: "kelengkapan_dokumen", label: "Kelengkapan Dokumen", checked: false },
  { key: "nama_rekening", label: "Nama Rekening", checked: false },
  { key: "no_rekening", label: "No. Rekening", checked: false },
  { key: "no_kartu_kredit", label: "No. Kartu Kredit", checked: false },
//   { key: "no_giro", label: "No. Giro", checked: false },
//   { key: "tanggal_giro", label: "Tanggal Giro", checked: false },
//   { key: "tanggal_cair", label: "Tanggal Cair", checked: false },
  { key: "keterangan", label: "Keterangan", checked: false },
  { key: "total", label: "Total", checked: false },
//   { key: "diskon", label: "Diskon", checked: false },
//   { key: "ppn", label: "PPN", checked: false },
//   { key: "ppn_nominal", label: "PPN Nominal", checked: false },
//   { key: "pph_nominal", label: "PPH Nominal", checked: false },
  { key: "grand_total", label: "Grand Total", checked: false },
  { key: "created_by", label: "Dibuat Oleh", checked: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false },
  ]
);

// Watch for prop changes - but prevent infinite loops
let isUpdatingFromProps = false;
watch(
  () => props.filters,
  (val) => {
    if (val && !isUpdatingFromProps) {
      isUpdatingFromProps = true;
      form.value = {
        tanggal_start: val.tanggal_start || "",
        tanggal_end: val.tanggal_end || "",
        department_id: val.department_id || "",
        status: val.status || "",
        tipe_pv: val.tipe_pv || "",
//         metode_bayar: val.metode_bayar || "",
        kelengkapan_dokumen: val.kelengkapan_dokumen || "",
        supplier_id: val.supplier_id || "",
        search: val.search ?? "",
        entriesPerPage: val.per_page || 10,
      };
      // Reset flag after a short delay to allow for normal updates
      setTimeout(() => {
        isUpdatingFromProps = false;
      }, 100);
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

// Computed options
const departmentOptions = computed(() => {
  return [
    { label: "Semua Departemen", value: "" },
    ...(props.departments || []).map((dept) => ({
      label: dept.name,
      value: dept.id.toString(),
    })),
  ];
});

const statusFilterOptions = computed(() => {
  const statusList = ["In Progress", "Verified", "Validated", "Approved", "Rejected"];
  return [
    { label: "Semua Status", value: "" },
    ...statusList.map((status) => ({
      label: status,
      value: status,
    })),
  ];
});

const tipePvFilterOptions = computed(() => {
  const tipeList = ["Reguler", "Anggaran", "Lainnya", "Pajak", "Manual"];
  return [
    { label: "Semua Tipe", value: "" },
    ...tipeList.map((tipe) => ({ label: tipe, value: tipe })),
  ];
});

// const metodePembayaranFilterOptions = computed(() => {
//   const metodeList = ["Transfer", //"Cek/Giro",
//     "Kredit"];
//   return [
//     { label: "Semua Metode", value: "" },
//     ...metodeList.map((metode) => ({
//       label: metode,
//       value: metode,
//     })),
//   ];
// });

// Watch individual filter changes and apply immediately
watch(
  () => form.value.tanggal_start,
  () => {
    if (form.value.tanggal_start !== undefined) applyFilters();
  }
);
watch(
  () => form.value.tanggal_end,
  () => {
    if (form.value.tanggal_end !== undefined) applyFilters();
  }
);
watch(
  () => form.value.department_id,
  () => {
    if (form.value.department_id !== undefined) applyFilters();
  }
);
watch(
  () => form.value.status,
  () => {
    if (form.value.status !== undefined) applyFilters();
  }
);
watch(
  () => form.value.tipe_pv,
  () => {
    if (form.value.tipe_pv !== undefined) applyFilters();
  }
);
// watch(
//   () => form.value.metode_bayar,
//   () => {
//     if (form.value.metode_bayar !== undefined) applyFilters();
//   }
// );
watch(
  () => form.value.kelengkapan_dokumen,
  () => {
    if (form.value.kelengkapan_dokumen !== undefined) applyFilters();
  }
);
watch(
  () => form.value.supplier_id,
  () => {
    if (form.value.supplier_id !== undefined) applyFilters();
  }
);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    // Prevent applying filters if we're currently updating from props
    if (!isUpdatingFromProps) {
      applyFilters();
    }
  }, 300);
};

function toggleFilters() {
  isFilterOpen.value = !isFilterOpen.value;
  localStorage.setItem(
    "paymentVoucherApprovalShowFilters",
    isFilterOpen.value ? "true" : "false"
  );
}

function applyFilters() {
  const selectedColumnKeys = localColumns.value
    .filter((col) => col.checked)
    .map((col) => col.key);

  const payload: Record<string, any> = {};

  // Only include filters that have values
  if (form.value.tanggal_start) payload.tanggal_start = form.value.tanggal_start;
  if (form.value.tanggal_end) payload.tanggal_end = form.value.tanggal_end;
  if (form.value.department_id) payload.department_id = form.value.department_id;
  if (form.value.status) payload.status = form.value.status;
  if (form.value.tipe_pv) payload.tipe_pv = form.value.tipe_pv;
//   if (form.value.metode_bayar)
//     payload.metode_bayar = form.value.metode_bayar;
  if (form.value.kelengkapan_dokumen !== "")
    payload.kelengkapan_dokumen = form.value.kelengkapan_dokumen;
  if (form.value.supplier_id) payload.supplier_id = form.value.supplier_id;

  // Handle search - always include search field, even if empty
  // This ensures that when search is cleared, it's properly sent to parent
  payload.search = form.value.search || "";

  // Only include search_columns if there's actual search content
  if (form.value.search && form.value.search.trim()) {
    payload.search_columns = selectedColumnKeys.join(",");
  }

  payload.per_page = form.value.entriesPerPage;

  emit("filter", payload);
}

function resetFilters() {
  form.value = {
    tanggal_start: "",
    tanggal_end: "",
    department_id: "",
    status: "",
    tipe_pv: "",
    // metode_bayar: "",
    kelengkapan_dokumen: "",
    supplier_id: "",
    search: "",
    entriesPerPage: 10,
  };
  applyFilters();
  emit("reset");
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

// Initialize filter state from localStorage
const savedFilterState = localStorage.getItem("paymentVoucherApprovalShowFilters");
if (savedFilterState !== null) {
  isFilterOpen.value = savedFilterState === "true";
}
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
