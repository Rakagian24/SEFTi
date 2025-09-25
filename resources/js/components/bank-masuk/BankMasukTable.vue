<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'
import { formatCurrencyWithSymbol } from '@/lib/currencyUtils'

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const props = defineProps<{
  bankMasuks: any,
  sortBy?: string,
  sortDirection?: string,
  columns?: Column[]
}>();

const emit = defineEmits(["edit", "delete", "detail", "log", "mutasi", "paginate", "sort", "select-rows", "update:columns"]);

// Default columns configuration
const defaultColumns: Column[] = [
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
];

const localColumns = ref<Column[]>(props.columns || defaultColumns);

// Watch for external changes
watch(() => props.columns, (newColumns) => {
  if (newColumns) {
    localColumns.value = newColumns;
  }
}, { immediate: true });

// Emit changes when columns change
watch(localColumns, (newColumns) => {
  emit('update:columns', newColumns);
}, { deep: true });

// Computed for visible columns
const visibleColumns = computed(() => {
  return localColumns.value.filter(col => col.checked);
});

function editRow(row: any) { emit("edit", row); }
function detailRow(row: any) { emit("detail", row); }
function logRow(row: any) { emit("log", row); }
function mutasiRow(row: any) { emit("mutasi", row); }

function goToPage(url: any) {
  emit("paginate", url);
  window.dispatchEvent(new CustomEvent("pagination-changed"));
  window.dispatchEvent(new CustomEvent("table-changed"));
}

function handleSort(column: string) {
  let direction = 'asc';
  if (props.sortBy === column) {
    direction = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  emit('sort', { sortBy: column, sortDirection: direction });
}

// Confirm dialog functionality
const showConfirm = ref(false)
const confirmRow = ref<any>(null)

function askDeleteRow(row: any) {
  confirmRow.value = row;
  showConfirm.value = true;
}
function onConfirmDelete() {
  emit('delete', confirmRow.value);
  showConfirm.value = false;
  confirmRow.value = null;
}
function onCancelDelete() {
  showConfirm.value = false;
  confirmRow.value = null;
}

// Tooltip functionality untuk perihal (note)
const activeTooltip = ref(null)

// Fungsi untuk toggle perihal tooltip
function togglePerihal(rowId: any, event: Event) {
  event.stopPropagation()
  if (activeTooltip.value === rowId) {
    activeTooltip.value = null
  } else {
    activeTooltip.value = rowId
  }
}

// Fungsi untuk menutup tooltip
function closeTooltip() {
  activeTooltip.value = null
}

// Fungsi untuk memotong teks perihal
function truncateText(text: string, maxLength: number = 50) {
  if (!text) return '-'
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

// Fungsi untuk mengecek apakah ada perihal (tidak kosong)
function hasPerihal(text: string) {
  return text && text.trim() !== ''
}

function formatTanggal(tgl: string) {
  if (!tgl) return '-';
  const d = new Date(tgl);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: '2-digit' });
}

// Function to get terima dari display value
function getTerimaDariDisplay(row: any) {
  if (row.terima_dari === 'Lainnya') {
    return row.input_lainnya || 'Lainnya';
  }
  return row.terima_dari || '-';
}

// Function to get bank account display
function getBankAccountDisplay(row: any) {
  if (row.bank_account) {
    const bank = row.bank_account.bank?.singkatan || 'Unknown';
    const accountNumber = row.bank_account.no_rekening;
    const last5 = accountNumber ? accountNumber.slice(-5) : '';
    return `${bank} - ******${last5}`;
  }
  return '-';
}

// Function to get currency display
function getCurrencyDisplay(row: any) {
  return row.bank_account?.bank?.currency || 'IDR';
}

const selectedIds = ref<number[]>([]);
const allRows = computed(() => props.bankMasuks?.data?.map((row: any) => row.id) || []);
const isAllSelected = computed(() => allRows.value.length > 0 && selectedIds.value.length === allRows.value.length);

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = [...allRows.value];
  }
  emit('select-rows', selectedIds.value);
}
function toggleSelectRow(id: number) {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((x) => x !== id);
  } else {
    selectedIds.value = [...selectedIds.value, id];
  }
  emit('select-rows', selectedIds.value);
}

// Reset selection when table changes
function handleTableChange() {
  selectedIds.value = [];
  emit('select-rows', selectedIds.value);
}

watch(() => props.bankMasuks?.data, () => {
  // Reset selection jika data berubah (misal ganti halaman)
  selectedIds.value = [];
});

onMounted(() => {
  window.addEventListener('table-changed', handleTableChange);
});

onUnmounted(() => {
  window.removeEventListener('table-changed', handleTableChange);
});
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-center align-middle">
              <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" />
            </th>

            <!-- No. BM -->
            <th
              v-if="visibleColumns.find(col => col.key === 'no_bm')"
              @click="handleSort('no_bm')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              No. BM
              <span v-if="$props.sortBy === 'no_bm'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- No. PV -->
            <th
              v-if="visibleColumns.find(col => col.key === 'no_pv')"
              @click="handleSort('purchase_order_id')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              No. PV
              <span v-if="$props.sortBy === 'purchase_order_id'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Tipe -->
            <th
              v-if="visibleColumns.find(col => col.key === 'tipe')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Tipe
            </th>

            <!-- Terima Dari -->
            <th
              v-if="visibleColumns.find(col => col.key === 'terima_dari')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Terima Dari
            </th>

            <!-- Nama Customer -->
            <th
              v-if="visibleColumns.find(col => col.key === 'customer')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Nama Customer
            </th>

            <!-- Tanggal Bank Masuk -->
            <th
              v-if="visibleColumns.find(col => col.key === 'tanggal')"
              @click="handleSort('tanggal')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Tanggal Bank Masuk
              <span v-if="$props.sortBy === 'tanggal'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Tanggal Match -->
            <th
              v-if="visibleColumns.find(col => col.key === 'match_date')"
              @click="handleSort('match_date')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Tanggal Match
              <span v-if="$props.sortBy === 'match_date'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Department -->
            <th
              v-if="visibleColumns.find(col => col.key === 'department')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Departemen
            </th>

            <!-- Bank Account -->
            <th
              v-if="visibleColumns.find(col => col.key === 'bank_account')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Rekening
            </th>

            <!-- Currency -->
            <th
              v-if="visibleColumns.find(col => col.key === 'currency')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Currency
            </th>

            <!-- Purchase Order -->
            <th
              v-if="visibleColumns.find(col => col.key === 'purchase_order')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Purchase Order
            </th>

            <!-- Note -->
            <th
              v-if="visibleColumns.find(col => col.key === 'note')"
              @click="handleSort('note')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Note
              <span v-if="$props.sortBy === 'note'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Nominal -->
            <th
              v-if="visibleColumns.find(col => col.key === 'nilai')"
              @click="handleSort('nilai')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Nominal Awal
              <span v-if="$props.sortBy === 'nilai'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Selisih Penambahan -->
            <th
              v-if="visibleColumns.find(col => col.key === 'selisih_penambahan')"
              @click="handleSort('selisih_penambahan')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Selisih Penambahan
              <span v-if="$props.sortBy === 'selisih_penambahan'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Selisih Pengurangan -->
            <th
              v-if="visibleColumns.find(col => col.key === 'selisih_pengurangan')"
              @click="handleSort('selisih_pengurangan')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Selisih Pengurangan
              <span v-if="$props.sortBy === 'selisih_pengurangan'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <!-- Nominal Akhir -->
            <th
              v-if="visibleColumns.find(col => col.key === 'nominal_akhir')"
              @click="handleSort('nominal_akhir')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Nominal Akhir
              <span v-if="$props.sortBy === 'nominal_akhir'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>

            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in bankMasuks?.data" :key="row.id" class="alternating-row" @click="closeTooltip()">
            <td class="px-6 py-4 text-center align-middle">
              <input type="checkbox" :checked="selectedIds.includes(row.id)" @change.stop="toggleSelectRow(row.id)" />
            </td>

            <!-- No. BM -->
            <td v-if="visibleColumns.find(col => col.key === 'no_bm')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm font-medium text-gray-900">
              {{ row.no_bm }}
            </td>

            <!-- No. PV -->
            <td v-if="visibleColumns.find(col => col.key === 'no_pv')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.purchase_order_id || '-' }}
            </td>

            <!-- Tipe -->
            <td v-if="visibleColumns.find(col => col.key === 'tipe')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.tipe_po || '-' }}
            </td>

            <!-- Terima Dari -->
            <td v-if="visibleColumns.find(col => col.key === 'terima_dari')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ getTerimaDariDisplay(row) }}
            </td>

            <!-- Nama Customer -->
            <td v-if="visibleColumns.find(col => col.key === 'customer')" class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.ar_partner?.nama_ap || '-' }}
            </td>

            <!-- Tanggal Bank Masuk -->
            <td v-if="visibleColumns.find(col => col.key === 'tanggal')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatTanggal(row.tanggal) }}
            </td>

            <!-- Tanggal Match -->
            <td v-if="visibleColumns.find(col => col.key === 'match_date')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatTanggal(row.match_date) }}
            </td>

            <!-- Department -->
            <td v-if="visibleColumns.find(col => col.key === 'department')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.bank_account?.department?.name || '-' }}
            </td>

            <!-- Bank Account -->
            <td v-if="visibleColumns.find(col => col.key === 'bank_account')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ getBankAccountDisplay(row) }}
            </td>

            <!-- Currency -->
            <td v-if="visibleColumns.find(col => col.key === 'currency')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ getCurrencyDisplay(row) }}
            </td>

            <!-- Purchase Order -->
            <td v-if="visibleColumns.find(col => col.key === 'purchase_order')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.purchase_order_id || '-' }}
            </td>

            <!-- Note -->
            <td v-if="visibleColumns.find(col => col.key === 'note')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010] relative">
              <div class="flex items-center">
                <span class="inline-block max-w-[200px] truncate">
                  {{ truncateText(row.note) }}
                </span>
                <button
                  v-if="hasPerihal(row.note)"
                  @click="togglePerihal(row.id, $event)"
                  class="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none flex-shrink-0"
                  :title="activeTooltip === row.id ? 'Tutup perihal lengkap' : 'Lihat perihal lengkap'"
                >
                  <svg
                    class="w-4 h-4 transform transition-transform duration-200"
                    :class="{ 'rotate-180': activeTooltip === row.id }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </button>
              </div>

              <!-- Floating tooltip untuk perihal lengkap -->
              <div
                v-if="activeTooltip === row.id && hasPerihal(row.note)"
                class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm w-80"
                style="min-width: 300px"
              >
                <div class="flex items-start justify-between mb-2">
                  <h4 class="text-sm font-semibold text-gray-900">Perihal Lengkap:</h4>
                  <button
                    @click="closeTooltip()"
                    class="text-gray-400 hover:text-gray-600 transition-colors ml-2"
                    title="Tutup"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>
                <div class="bg-gray-50 rounded-md p-3 border border-gray-100">
                  <p
                    class="text-sm text-gray-700 leading-relaxed whitespace-pre-line select-text"
                  >
                    {{ row.note }}
                  </p>
                </div>
                <!-- Arrow pointer -->
                <div
                  class="absolute -top-2 left-6 w-4 h-4 bg-white border-l border-t border-gray-200 transform rotate-45"
                ></div>
              </div>
            </td>

            <!-- Nominal -->
            <td v-if="visibleColumns.find(col => col.key === 'nilai')" class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010] font-medium">
              {{ formatCurrencyWithSymbol(row.nilai, row.bank_account?.bank?.currency) }}
            </td>

            <!-- Selisih Penambahan -->
            <td v-if="visibleColumns.find(col => col.key === 'selisih_penambahan')" class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.selisih_penambahan ? formatCurrencyWithSymbol(row.selisih_penambahan, row.bank_account?.bank?.currency) : '-' }}
            </td>

            <!-- Selisih Pengurangan -->
            <td v-if="visibleColumns.find(col => col.key === 'selisih_pengurangan')" class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.selisih_pengurangan ? formatCurrencyWithSymbol(row.selisih_pengurangan, row.bank_account?.bank?.currency) : '-' }}
            </td>

            <!-- Nominal Akhir -->
            <td v-if="visibleColumns.find(col => col.key === 'nominal_akhir')" class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010] font-medium">
              {{ row.nominal_akhir ? formatCurrencyWithSymbol(row.nominal_akhir, row.bank_account?.bank?.currency) : '-' }}
            </td>

            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit Button -->
                <button
                  @click="editRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200"
                  title="Edit"
                >
                  <svg
                    class="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>

                <!-- Delete Button -->
                <button
                  @click="askDeleteRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Hapus"
                >
                  <svg
                    class="w-4 h-4 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>

                <!-- Detail Button -->
                <button
                  @click="detailRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200"
                  title="Detail"
                >
                  <svg
                    class="w-4 h-4 text-green-600"
                    viewBox="0 0 16 16"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path d="M15 1H1V3H15V1Z" fill="currentColor" />
                    <path
                      d="M11 5H1V7H6.52779C7.62643 5.7725 9.223 5 11 5Z"
                      fill="currentColor"
                    />
                    <path
                      d="M5.34141 13C5.60482 13.7452 6.01127 14.4229 6.52779 15H1V13H5.34141Z"
                      fill="currentColor"
                    />
                    <path
                      d="M5.34141 9C5.12031 9.62556 5 10.2987 5 11H1V9H5.34141Z"
                      fill="currentColor"
                    />
                    <path
                      d="M15 11C15 11.7418 14.7981 12.4365 14.4462 13.032L15.9571 14.5429L14.5429 15.9571L13.032 14.4462C12.4365 14.7981 11.7418 15 11 15C8.79086 15 7 13.2091 7 11C7 8.79086 8.79086 7 11 7C13.2091 7 15 8.79086 15 11Z"
                      fill="currentColor"
                    />
                  </svg>
                </button>

                <!-- Log Button -->
                <button
                  @click="logRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
                  title="Log"
                >
                  <svg
                    class="w-4 h-4 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                </button>

                <!-- Mutasi Button -->
                <button
                  @click="mutasiRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200"
                  title="Mutasi"
                >
                  <svg
                    class="w-4 h-4 text-purple-700"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="goToPage(bankMasuks?.prev_page_url)"
          :disabled="!bankMasuks?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            bankMasuks?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <!-- Page Numbers -->
        <template v-for="(link, index) in bankMasuks?.links?.slice(1, -1)" :key="index">
          <button
            @click="goToPage(link.url)"
            :disabled="!link.url"
            :class="[
              'w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200',
              link.active
                ? 'bg-black text-white'
                : link.url
                ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed',
            ]"
            v-html="link.label"
          ></button>
        </template>

        <!-- Next Button -->
        <button
          @click="goToPage(bankMasuks?.next_page_url)"
          :disabled="!bankMasuks?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            bankMasuks?.next_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Next
        </button>
      </nav>
    </div>

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      :show="showConfirm"
      title="Konfirmasi Hapus"
      message="Apakah Anda yakin ingin menghapus data ini?"
      @confirm="onConfirmDelete"
      @cancel="onCancelDelete"
    />
  </div>
</template>

<style scoped>
/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Ensure action column stays visible during horizontal scroll */
.sticky {
  position: sticky;
  z-index: 10;
}

/* Alternating row colors */
.alternating-row:nth-child(even) {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

/* Hover effect for alternating rows */
.alternating-row:nth-child(even):hover {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover {
  background-color: #f8fafc;
}

/* Action cell background matching parent row */
.alternating-row:nth-child(even) .action-cell {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) .action-cell {
  background-color: #ffffff;
}

.alternating-row:nth-child(even):hover .action-cell {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover .action-cell {
  background-color: #f8fafc;
}

/* Pagination styling enhancements */
nav button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Active page button styling */
nav button.z-10 {
  background-color: #2563eb !important;
  border-color: #2563eb !important;
  color: white !important;
  font-weight: 600;
}

/* Disabled button styling */
nav button:disabled {
  opacity: 0.5;
}

/* Hover effects for pagination buttons */
nav button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Tooltip animations */
.tooltip-enter-active,
.tooltip-leave-active {
  transition: all 0.2s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
