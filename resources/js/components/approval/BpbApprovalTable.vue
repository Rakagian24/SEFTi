<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="p-4" v-if="loading">
      <div class="text-sm text-gray-500">Loading...</div>
    </div>

    <div v-else class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-center align-middle" v-if="hasSelectableRows">
              <input
                type="checkbox"
                :checked="allSelectableSelected"
                @change="toggleSelectAll($event)"
                :disabled="!hasSelectableRows"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
            </th>
            <th v-if="isVisible('no_bpb')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. BPB</th>
            <th v-if="isVisible('no_po')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. PO</th>
            <th v-if="isVisible('no_pv')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. PV</th>
            <th v-if="isVisible('tanggal')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal</th>
            <th v-if="isVisible('status')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th v-if="isVisible('supplier')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Supplier</th>
            <th v-if="isVisible('department')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Departemen</th>
            <th v-if="isVisible('perihal')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Perihal (PO)</th>
            <th v-if="isVisible('subtotal')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Subtotal</th>
            <th v-if="isVisible('diskon')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Diskon</th>
            <th v-if="isVisible('dpp')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">DPP</th>
            <th v-if="isVisible('ppn')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">PPN</th>
            <th v-if="isVisible('pph')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">PPH</th>
            <th v-if="isVisible('grand_total')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Grand Total</th>
            <th v-if="isVisible('keterangan')" class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Keterangan</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in data" :key="row.id" class="alternating-row">
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <input
                type="checkbox"
                :disabled="!isSelectable(row)"
                :checked="selected.includes(row.id)"
                @change="toggleRow(row)"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
            </td>
            <td v-if="isVisible('no_bpb')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              <span class="font-medium text-gray-900">{{ row.no_bpb || '-' }}</span>
            </td>
            <td v-if="isVisible('no_po')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.purchase_order?.no_po || '-' }}</td>
            <td v-if="isVisible('no_pv')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.payment_voucher?.no_pv || '-' }}</td>
            <td v-if="isVisible('tanggal')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.tanggal ? formatDate(row.tanggal) : '-' }}</td>
            <td v-if="isVisible('status')" class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(row.status)">{{ row.status }}</span>
            </td>
            <td v-if="isVisible('supplier')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.supplier?.nama_supplier || '-' }}</td>
            <td v-if="isVisible('department')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.department?.name || '-' }}</td>
            <td v-if="isVisible('perihal')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.purchase_order?.perihal?.nama || '-' }}</td>
            <td v-if="isVisible('subtotal')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.subtotal) }}</td>
            <td v-if="isVisible('diskon')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.diskon) }}</td>
            <td v-if="isVisible('dpp')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.dpp) }}</td>
            <td v-if="isVisible('ppn')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.ppn) }}</td>
            <td v-if="isVisible('pph')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.pph) }}</td>
            <td v-if="isVisible('grand_total')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.grand_total) }}</td>
            <td v-if="isVisible('keterangan')" class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.keterangan || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Detail -->
                <button
                  @click="$emit('action', { action: 'detail', row })"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200"
                  title="Detail"
                >
                  <svg class="w-4 h-4 text-green-600" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 1H1V3H15V1Z" fill="currentColor" />
                    <path d="M11 5H1V7H6.52779C7.62643 5.7725 9.223 5 11 5Z" fill="currentColor" />
                    <path d="M5.34141 13C5.60482 13.7452 6.01127 14.4229 6.52779 15H1V13H5.34141Z" fill="currentColor" />
                    <path d="M5.34141 9C5.12031 9.62556 5 10.2987 5 11H1V9H5.34141Z" fill="currentColor" />
                    <path d="M15 11C15 11.7418 14.7981 12.4365 14.4462 13.032L15.9571 14.5429L14.5429 15.9571L13.032 14.4462C12.4365 14.7981 11.7418 15 11 15C8.79086 15 7 13.2091 7 11C7 8.79086 8.79086 7 11 7C13.2091 7 15 8.79086 15 11Z" fill="currentColor" />
                  </svg>
                </button>

                <!-- Download -->
                <button
                  v-if="row.status !== 'Draft' && row.status !== 'Rejected'"
                  @click="$emit('action', { action: 'download', row })"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200"
                  title="Unduh"
                >
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>

                <!-- Log -->
                <button
                  @click="$emit('action', { action: 'log', row })"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
                  title="Log Activity"
                >
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!data || data.length === 0">
            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="pagination"
      class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
    >
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <button
          @click="$emit('paginate', pagination?.prev_page_url)"
          :disabled="!pagination?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pagination?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <template v-for="(link, index) in (pagination?.links || []).slice(1, -1)" :key="index">
          <button
            @click="$emit('paginate', link.url)"
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
          @click="$emit('paginate', pagination?.next_page_url)"
          :disabled="!pagination?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pagination?.next_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Next
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Column { key: string; label: string; checked: boolean; sortable?: boolean }

const props = defineProps<{
  data: any[];
  loading: boolean;
  selected: number[];
  pagination: any | null;
  selectableStatuses: string[];
  isRowSelectable: (row: any) => boolean;
  columns?: Column[];
}>();

const emit = defineEmits<{
  (e: 'select', value: number[]): void;
  (e: 'action', payload: { action: string; row: any }): void;
  (e: 'paginate', url: string): void;
}>();

const allSelectableSelected = computed(() => {
  const selectableIds = (props.data || []).filter(isSelectable).map((r: any) => r.id);
  return selectableIds.length > 0 && selectableIds.every((id: number) => props.selected.includes(id));
});

const hasSelectableRows = computed(() => (props.data || []).some((r: any) => isSelectable(r)));

function toggleSelectAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  const selectableIds = (props.data || []).filter(isSelectable).map((r: any) => r.id);
  emit('select', checked ? selectableIds : []);
}

function toggleRow(row: any) {
  const id = row.id;
  const next = props.selected.includes(id)
    ? props.selected.filter((x) => x !== id)
    : [...props.selected, id];
  emit('select', next);
}

function isSelectable(row: any) {
  return props.selectableStatuses.includes(row.status) && props.isRowSelectable(row);
}

function statusClass(status: string) {
  switch (status) {
    case 'Approved':
      return 'bg-green-100 text-green-800';
    case 'Rejected':
      return 'bg-red-100 text-red-800';
    case 'In Progress':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function formatDate(d: string | null | undefined) {
  if (!d) return '-';
  try { return new Date(d).toLocaleDateString('id-ID'); } catch { return d; }
}

// Visibility based on selected columns
const visibleKeys = computed<string[]>(() => {
  return (props.columns || []).filter((c: any) => c && (c as any).checked).map((c: any) => (c as any).key);
});

function isVisible(key: string) {
  if (!props.columns || (props.columns || []).length === 0) return true;
  return visibleKeys.value.includes(key);
}

function formatCurrency(val: any) {
  const n = Number(val);
  if (isNaN(n)) return '-';
  return n.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
}
</script>

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
</style>
