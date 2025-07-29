<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'

const props = defineProps<{ bankMasuks: any, sortBy?: string, sortDirection?: string }>();
const emit = defineEmits(["edit", "delete", "detail", "log", "paginate", "sort", "select-rows"]);

function editRow(row: any) { emit("edit", row); }
function detailRow(row: any) { emit("detail", row); }
function logRow(row: any) { emit("log", row); }

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

// Fungsi untuk format currency berdasarkan currency bank
function formatCurrency(amount: number, currency: string = 'IDR') {
  if (!amount) return '-';

  // Handle decimal numbers
  const number_string = String(amount).replace(/[^\d.]/g, "");
  if (!number_string) return "-";

  // Split by decimal point
  const parts = number_string.split('.');
  const wholePart = parts[0];
  const decimalPart = parts[1] || '';

  // Format whole part with thousand separators
  const sisa = wholePart.length % 3;
  let formatted = wholePart.substr(0, sisa);
  const ribuan = wholePart.substr(sisa).match(/\d{3}/g);
  if (ribuan) {
    formatted += (sisa ? "." : "") + ribuan.join(".");
  }

  // Add decimal part if exists (support up to 5 decimal places)
  if (decimalPart) {
    // Limit to 5 decimal places
    const limitedDecimalPart = decimalPart.substring(0, 5);
    formatted += "," + limitedDecimalPart;
  }

  // Tentukan symbol berdasarkan currency
  let symbol = 'Rp ';
  if (currency === 'USD') {
    symbol = '$';
  }

  return symbol + formatted;
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
            <th @click="handleSort('no_bm')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none">
              No. BM
              <span v-if="$props.sortBy === 'no_bm'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th @click="handleSort('purchase_order_id')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none">
              No. PV
              <span v-if="$props.sortBy === 'purchase_order_id'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th @click="handleSort('tanggal')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none">
              Tanggal
              <span v-if="$props.sortBy === 'tanggal'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th @click="handleSort('note')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none">
              Perihal
              <span v-if="$props.sortBy === 'note'">
                <svg v-if="$props.sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th @click="handleSort('nilai')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none">
              Nominal
              <span v-if="$props.sortBy === 'nilai'">
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
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm font-medium text-gray-900">
              {{ row.no_bm }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.purchase_order_id || '-' }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatTanggal(row.tanggal) }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010] relative">
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
            <td class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010] font-medium">
              {{ formatCurrency(row.nilai, row.bank_account?.bank?.currency) }}
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
                  @click.stop="detailRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200"
                  title="Detail"
                >
                  <svg
                    class="w-4 h-4 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>

                <!-- Download Button -->
                <!-- <button
                  @click="$inertia.get(`/bank-masuk/${row.id}/download`)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-cyan-50 hover:bg-cyan-100 transition-colors duration-200"
                  title="Unduh"
                >
                  <svg
                    class="w-4 h-4 text-cyan-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                </button> -->

                <!-- Log Activity Button -->
                <button
                  @click="logRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
                  title="Log Activity"
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
  </div>

  <ConfirmDialog
    :show="showConfirm"
    message="Apakah Anda yakin ingin menghapus data bank masuk ini secara permanen?"
    @confirm="onConfirmDelete"
    @cancel="onCancelDelete"
  />
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
