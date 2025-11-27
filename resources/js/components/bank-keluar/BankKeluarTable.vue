<script setup lang="ts">
import { formatCurrencyWithSymbol } from '@/lib/currencyUtils';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationMeta {
  from: number | null;
  to: number | null;
  total: number;
  prev_page_url?: string | null;
  next_page_url?: string | null;
  links: PaginationLink[];
}

interface BankKeluarRow {
  id: number | string;
  no_bk: string;
  tanggal: string | Date;
  nominal: number;
  payment_voucher?: { no_pv?: string } | null;
  department?: { name?: string } | null;
  perihal?: { name?: string } | null;
  documents?: Array<{ id: number | string; original_filename?: string | null }>;
}

type BankKeluarPagination = PaginationMeta & { data: BankKeluarRow[] };

const props = defineProps<{
  bankKeluars: BankKeluarPagination | null;
  sortBy?: string;
  sortDirection?: 'asc' | 'desc';
}>();

const emit = defineEmits<{
  (e: 'edit', row: BankKeluarRow): void;
  (e: 'delete', row: BankKeluarRow): void;
  (e: 'detail', row: BankKeluarRow): void;
  (e: 'log', row: BankKeluarRow): void;
  (e: 'paginate', url: string | null | undefined): void;
  (e: 'sort', payload: { sortBy: string; sortDirection: 'asc' | 'desc' }): void;
}>();

const showConfirmDelete = ref(false);
const confirmDeleteMessage = ref('Apakah Anda yakin ingin menghapus data Bank Keluar ini?');
const rowToDelete = ref<BankKeluarRow | null>(null);

function editRow(row: BankKeluarRow) {
  emit('edit', row);
}

function detailRow(row: BankKeluarRow) {
  emit('detail', row);
}

function logRow(row: BankKeluarRow) {
  emit('log', row);
}

// function viewDocument(row: BankKeluarRow) {
//   const docs = row.documents || [];
//   if (!docs.length) return;
//   const docId = docs[0]?.id;
//   if (!docId) return;
//   window.open(`/bank-keluar/documents/${docId}/view`, '_blank');
// }

function confirmDelete(row: BankKeluarRow) {
  rowToDelete.value = row;
  showConfirmDelete.value = true;
}

function performDelete() {
  if (!rowToDelete.value) return;
  emit('delete', rowToDelete.value);
  rowToDelete.value = null;
  showConfirmDelete.value = false;
}

function goToPage(url: string | null | undefined) {
  emit('paginate', url);
}

function handleSort(column: string) {
  let direction: 'asc' | 'desc' = 'asc';
  if (props.sortBy === column) {
    direction = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  emit('sort', { sortBy: column, sortDirection: direction });
}

function formatTanggal(tgl: string | Date | null | undefined) {
  if (!tgl) return '-';
  const d = new Date(tgl);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: '2-digit' });
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              @click="handleSort('no_bk')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              No. BK
              <span v-if="sortBy === 'no_bk'">
                <svg v-if="sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              No. PV
            </th>
            <th
              @click="handleSort('tanggal')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Tanggal
              <span v-if="sortBy === 'tanggal'">
                <svg v-if="sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Departemen
            </th>
            <th
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Perihal
            </th>
            <th
              @click="handleSort('nominal')"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap cursor-pointer select-none"
            >
              Nominal
              <span v-if="sortBy === 'nominal'">
                <svg v-if="sortDirection === 'asc'" class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]"
            >
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="row in bankKeluars?.data"
            :key="row.id"
            class="alternating-row"
          >
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm font-medium text-gray-900">
              {{ row.no_bk }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.payment_voucher?.no_pv || '-' }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatTanggal(row.tanggal) }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.department?.name || '-' }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.perihal?.name || '-' }}
            </td>
            <td class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010] font-medium">
              {{ formatCurrencyWithSymbol(row.nominal, 'IDR') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
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
                <button
                  @click="confirmDelete(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Delete"
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
                <!-- <button
                  v-if="row.documents && row.documents.length"
                  @click="viewDocument(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-indigo-50 hover:bg-indigo-100 transition-colors duration-200"
                  title="Lihat Bukti Bayar"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 text-indigo-600"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button> -->
              </div>
            </td>
          </tr>
          <tr v-if="!bankKeluars?.data?.length">
            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
              No data available
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <button
          @click="goToPage(bankKeluars?.prev_page_url)"
          :disabled="!bankKeluars?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            bankKeluars?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <template v-for="(link, index) in bankKeluars?.links?.slice(1, -1)" :key="index">
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

        <button
          @click="goToPage(bankKeluars?.next_page_url)"
          :disabled="!bankKeluars?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            bankKeluars?.next_page_url
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
    :show="showConfirmDelete"
    :message="confirmDeleteMessage"
    @confirm="performDelete()"
    @cancel="showConfirmDelete = false; rowToDelete = null;"
  />
</template>

<style scoped>
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

.sticky {
  position: sticky;
  z-index: 10;
}

.alternating-row:nth-child(even) {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

.alternating-row:nth-child(even):hover {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover {
  background-color: #f8fafc;
}

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
</style>

