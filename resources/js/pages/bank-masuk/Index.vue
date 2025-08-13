<script setup lang="ts">
import { ref, onUnmounted, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import BankMasukFilter from '@/components/bank-masuk/BankMasukFilter.vue';
import BankMasukForm from '@/components/bank-masuk/BankMasukForm.vue';
import BankMasukTable from '@/components/bank-masuk/BankMasukTable.vue';
import BankMasukSummary from '@/components/bank-masuk/BankMasukSummary.vue';
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { useSecureDownload } from "@/composables/useSecureDownload";
import { getIconForPage } from "@/lib/iconMapping";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Masuk" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();
const { downloadFile } = useSecureDownload();

interface Department {
  id: number;
  name: string;
  status: string;
}

interface BankAccount {
  id: number;
  no_rekening: string;
  bank_id: number;
  department_id: number;
  status: string;
  bank?: any;
  department?: any;
}

interface SummaryData {
  total_count: number;
  total_idr: number;
  total_usd: number;
  total_matched: number;
}

const props = defineProps({
  bankMasuks: Object,
  filters: Object,
  bankAccounts: Array as () => BankAccount[],
  departments: Array as () => Department[],
  arPartners: Array,
  summary: Object as () => SummaryData
});

// Make data reactive using computed properties
const bankMasuks = computed(() => props.bankMasuks);
const filters = computed(() => props.filters as any || {});
const bankAccounts = computed(() => Array.isArray(props.bankAccounts) ? props.bankAccounts : []);
const departments = computed(() => Array.isArray(props.departments) ? props.departments : []);

const arPartners = computed(() => Array.isArray(props.arPartners) ? props.arPartners : []);
const entriesPerPage = ref(props.filters?.entriesPerPage || 10);
const searchQuery = ref(props.filters?.search || '');
const sortBy = ref((props.filters && props.filters.sortBy) || '');
const sortDirection = ref((props.filters && props.filters.sortDirection) || '');

// Column configuration
const tableColumns = ref([
  { key: 'no_bm', label: 'No. BM', checked: true, sortable: true },
  { key: 'no_pv', label: 'No. PV', checked: false, sortable: true },
  { key: 'tipe', label: 'Tipe', checked: false, sortable: false },
  { key: 'terima_dari', label: 'Terima Dari', checked: false, sortable: false },
  { key: 'customer', label: 'Nama Customer', checked: false, sortable: false },
  { key: 'tanggal', label: 'Tanggal Bank Masuk', checked: true, sortable: true },
  { key: 'match_date', label: 'Tanggal Match', checked: false, sortable: true },
  { key: 'department', label: 'Departemen', checked: true, sortable: false },
  { key: 'bank_account', label: 'Rekening', checked: true, sortable: false },
  { key: 'currency', label: 'Currency', checked: true, sortable: false },
  { key: 'purchase_order', label: 'Purchase Order', checked: false, sortable: false },
  { key: 'note', label: 'Note', checked: false, sortable: true },
  { key: 'nilai', label: 'Nominal Awal', checked: true, sortable: true },
  { key: 'selisih_penambahan', label: 'Selisih Penambahan', checked: false, sortable: true },
  { key: 'selisih_pengurangan', label: 'Selisih Pengurangan', checked: false, sortable: true },
  { key: 'nominal_akhir', label: 'Nominal Akhir', checked: true, sortable: true },
]);

// Debounce timer untuk search
let searchTimeout: ReturnType<typeof setTimeout>;

const showForm = ref(false);

// Watch search dengan debounce
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500); // 500ms debounce
}, { immediate: false });

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (sortBy.value) params.sortBy = sortBy.value;
  if (sortDirection.value) params.sortDirection = sortDirection.value;

  router.get('/bank-masuk', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

const editData = ref<Record<string, any> | undefined>(undefined);
const selectedIds = ref<number[]>([]);
const showExportModal = ref(false);
const exportFields = ref([
  { key: 'no_bm', label: 'No. Bank Masuk', checked: true },
  { key: 'tanggal', label: 'Tanggal', checked: true },
  { key: 'tipe_po', label: 'Tipe PO', checked: false },
  { key: 'terima_dari', label: 'Terima Dari', checked: false },
  { key: 'input_lainnya', label: 'Input Lainnya', checked: false },
  { key: 'purchase_order_id', label: 'Purchase Order', checked: false },
  { key: 'bank_account', label: 'Departemen (Nama Pemilik)', checked: false },
  { key: 'no_rekening', label: 'No. Rekening', checked: false },
  { key: 'nilai', label: 'Nominal Awal', checked: true },
  { key: 'selisih_penambahan', label: 'Selisih Penambahan', checked: false },
  { key: 'selisih_pengurangan', label: 'Selisih Pengurangan', checked: false },
  { key: 'nominal_akhir', label: 'Nominal Akhir', checked: true },
  { key: 'note', label: 'Note', checked: false },
  { key: 'created_at', label: 'Created At', checked: false },
  { key: 'updated_at', label: 'Updated At', checked: false },
  { key: 'created_by', label: 'Created By', checked: false },
  { key: 'updated_by', label: 'Updated By', checked: false },
]);

function openForm(row: Record<string, any> | undefined = undefined) {
  editData.value = row;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editData.value = undefined;
  // Refresh table otomatis ketika form ditutup
  handleRefreshTable();
}

function handleFilterChange(newFilters: any) {
  // Ensure all filter parameters are included
  const filterParams = {
    ...filters.value,
    ...newFilters,
    per_page: newFilters.per_page || entriesPerPage.value,
    search: newFilters.search || searchQuery.value,
    page: 1,
    // Include all filter parameters
    start: newFilters.start || filters.value.start,
    end: newFilters.end || filters.value.end,
    no_bm: newFilters.no_bm || filters.value.no_bm,
    no_pv: newFilters.no_pv || filters.value.no_pv,
    department_id: newFilters.department_id || filters.value.department_id,
    bank_account_id: newFilters.bank_account_id || filters.value.bank_account_id,
    terima_dari: newFilters.terima_dari || filters.value.terima_dari,
  };

  console.log('Sending filter params:', filterParams);
  console.log('Current filters:', filters.value);
  console.log('New filters:', newFilters);

  router.get('/bank-masuk', filterParams, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      console.log('Filter change successful');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: (errors) => {
      console.error('Filter change failed:', errors);
    }
  });
}

function handlePaginate(url: any) {
  // Ambil query string dari url dan merge dengan filter
  const urlObj = new URL(url, window.location.origin);
  const params = Object.fromEntries(urlObj.searchParams.entries());
  router.get('/bank-masuk', {
    ...filters.value,
    ...params,
    per_page: params.per_page || entriesPerPage.value,
    search: params.search || searchQuery.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handleSort({ sortBy: newSortBy, sortDirection: newSortDirection }: { sortBy: string, sortDirection: string }) {
  sortBy.value = newSortBy;
  sortDirection.value = newSortDirection;
  router.get('/bank-masuk', {
    ...filters.value,
    sortBy: newSortBy,
    sortDirection: newSortDirection,
    per_page: entriesPerPage.value,
    search: searchQuery.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handleEdit(row: any) {
  openForm(row);
}

function handleDetail(row: any) {
  router.get(`/bank-masuk/${row.id}`);
}

function handleLog(row: any) {
  router.get(`/bank-masuk/${row.id}/log`);
}

function handleDelete(row: any) {
  // Remove browser confirm, direct delete like supplier
  router.delete(`/bank-masuk/${row.id}`, {
    onSuccess: () => {
      addSuccess('Data bank masuk berhasil dihapus');
      // Refresh data setelah delete
      handleRefreshTable();
    },
    onError: () => {
      addError('Terjadi kesalahan saat menghapus data');
    }
  });
}

function handleRefreshTable() {
  // Refresh data dengan parameter yang sama
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (sortBy.value) params.sortBy = sortBy.value;
  if (sortDirection.value) params.sortDirection = sortDirection.value;

  router.get('/bank-masuk', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Reset selection setelah refresh
      selectedIds.value = [];
    }
  });
}

function handleResetFilters() {
  console.log('Resetting filters...');

  // Reset local state
  searchQuery.value = '';
  entriesPerPage.value = 10;
  sortBy.value = '';
  sortDirection.value = '';

  // Only send essential parameters for reset, avoid empty strings
  const resetParams = {
    per_page: 10,
    page: 1,
  };

  console.log('Sending reset params:', resetParams);

  router.get('/bank-masuk', resetParams, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      console.log('Reset successful');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: (errors) => {
      console.error('Reset failed:', errors);
    }
  });
}

// Cleanup timeout on unmount
onUnmounted(() => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
});

function handleSelectRows(ids: number[]) {
  selectedIds.value = ids;
}

function handleUpdateColumns(columns: any[]) {
  tableColumns.value = columns;
}

function openExportModal() {
  showExportModal.value = true;
}
function closeExportModal() {
  showExportModal.value = false;
}
async function confirmExport() {
  const selectedFields = exportFields.value.filter(f => f.checked).map(f => f.key);
  if (selectedFields.length === 0) {
    alert('Pilih minimal satu kolom untuk diexport!');
    return;
  }
  showExportModal.value = false;
  await exportToExcel(selectedFields);
}
async function exportToExcel(fields?: string[]) {
  if (selectedIds.value.length === 0) {
    alert('Pilih data yang ingin diexport!');
    return;
  }
  if (!fields || fields.length === 0) {
    alert('Pilih kolom yang ingin diexport!');
    return;
  }
  try {
    await downloadFile('/bank-masuk/export-excel', 'bank_masuk_export.xlsx', {
      ids: selectedIds.value,
      fields,
    });
    addSuccess('File Excel berhasil diunduh.');
  } catch (e: any) {
    console.error('Export error:', e);
    addError(e?.response?.data?.message || 'Gagal export data');
  }
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bank Masuk</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <component :is="getIconForPage('Bank Masuk')" class="w-4 h-4 mr-1" />
            Manage Bank Masuk data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Export to Excel Button -->
          <button
            @click="openExportModal"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0,0,256,256" fill="currentColor">
              <g fill="currentColor" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                <g transform="scale(5.12,5.12)">
                  <path d="M28.875,0c-0.01953,0.00781 -0.04297,0.01953 -0.0625,0.03125l-28,5.3125c-0.47656,0.08984 -0.82031,0.51172 -0.8125,1v37.3125c-0.00781,0.48828 0.33594,0.91016 0.8125,1l28,5.3125c0.28906,0.05469 0.58984,-0.01953 0.82031,-0.20703c0.22656,-0.1875 0.36328,-0.46484 0.36719,-0.76172v-5h17c1.09375,0 2,-0.90625 2,-2v-34c0,-1.09375 -0.90625,-2 -2,-2h-17v-5c0.00391,-0.28906 -0.12109,-0.5625 -0.33594,-0.75391c-0.21484,-0.19141 -0.50391,-0.28125 -0.78906,-0.24609zM28,2.1875v4.34375c-0.13281,0.27734 -0.13281,0.59766 0,0.875v35.40625c-0.02734,0.13281 -0.02734,0.27344 0,0.40625v4.59375l-26,-4.96875v-35.6875zM30,8h17v34h-17v-5h4v-2h-4v-6h4v-2h-4v-5h4v-2h-4v-5h4v-2h-4zM36,13v2h8v-2zM6.6875,15.6875l5.46875,9.34375l-5.96875,9.34375h5l3.25,-6.03125c0.22656,-0.58203 0.375,-1.02734 0.4375,-1.3125h0.03125c0.12891,0.60938 0.25391,1.02344 0.375,1.25l3.25,6.09375h4.96875l-5.75,-9.4375l5.59375,-9.25h-4.6875l-2.96875,5.53125c-0.28516,0.72266 -0.48828,1.29297 -0.59375,1.65625h-0.03125c-0.16406,-0.60937 -0.35156,-1.15234 -0.5625,-1.59375l-2.6875,-5.59375zM36,20v2h8v-2zM36,27v2h8v-2zM36,35v2h8v-2z"></path>
                </g>
              </g>
            </svg>
            Export to Excel
          </button>
          <!-- Add New Button -->
          <button
            @click="openForm()"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Add New
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <BankMasukSummary
        :summary="summary || { total_count: 0, total_idr: 0, total_usd: 0, total_matched: 0 }"
        :loading="false"
      />

      <!-- Filter Section -->
      <BankMasukFilter
        :filters="filters"
        :bankAccounts="bankAccounts"
        :departments="departments"
        :columns="tableColumns"
        v-model:entries-per-page="entriesPerPage"
        v-model:search="searchQuery"
        @change="handleFilterChange"
        @reset="handleResetFilters"
        @update:columns="handleUpdateColumns"
      />

      <!-- Table Section -->
      <BankMasukTable
        :bankMasuks="bankMasuks"
        :sortBy="sortBy"
        :sortDirection="sortDirection"
        :columns="tableColumns"
        @edit="handleEdit"
        @detail="handleDetail"
        @log="handleLog"
        @delete="handleDelete"
        @paginate="handlePaginate"
        @sort="handleSort"
        @select-rows="handleSelectRows"
        @add="openForm"
        @update-columns="handleUpdateColumns"
      />

      <!-- Form Modal -->
      <BankMasukForm
        v-if="showForm"
        :editData="editData"
        :bankAccounts="bankAccounts"
        :departments="departments"
        :arPartners="arPartners"
        @close="closeForm"
        @refreshTable="handleRefreshTable"
      />

      <!-- Export Modal -->
      <div v-if="showExportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
          <h2 class="text-lg font-semibold mb-4">Pilih Kolom untuk Export</h2>
          <form @submit.prevent="confirmExport">
            <div class="grid grid-cols-1 gap-2 mb-4 max-h-64 overflow-y-auto">
              <label v-for="field in exportFields" :key="field.key" class="flex items-center gap-2">
                <input type="checkbox" v-model="field.checked" />
                <span>{{ field.label }}</span>
              </label>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" @click="closeExportModal" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Batal</button>
              <button type="submit" class="px-4 py-2 rounded bg-green-600 hover:bg-green-700 text-white">Export</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
