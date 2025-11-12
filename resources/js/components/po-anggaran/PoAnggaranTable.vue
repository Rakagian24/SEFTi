<template>
  <!-- Empty state omitted to keep consistent minimal behavior -->
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th v-if="showCheckbox" class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
            </th>
            <th v-for="column in visibleColumns" :key="column.key" class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap" :class="getColumnClass(column.key)">
              {{ column.label }}
            </th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in props.data" :key="row.id" class="alternating-row">
            <td v-if="showCheckbox" class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              <input v-if="(row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin)" type="checkbox" :value="row.id" v-model="selectedIds" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
            </td>
            <td v-for="column in visibleColumns" :key="column.key" class="px-6 py-4 whitespace-nowrap text-sm" :class="getCellClass(column.key)">
              <template v-if="column.key === 'department'">
                {{ row.department?.name || '-' }}
              </template>
              <template v-else-if="column.key === 'perihal'">
                {{ row.perihal?.nama || '-' }}
              </template>
              <template v-else-if="column.key === 'bank'">
                {{ row.bank?.nama_bank || row.bank?.singkatan || '-' }}
              </template>
              <template v-else-if="column.key === 'bisnis_partner'">
                {{ row.bisnis_partner?.nama_bp || row.bisnisPartner?.nama_bp || '-' }}
              </template>
              <template v-else-if="column.key === 'metode_pembayaran'">
                {{ row.metode_pembayaran || '-' }}
              </template>
              <template v-else-if="column.key === 'tanggal'">
                {{ row.tanggal ? formatDate(row.tanggal) : '-' }}
              </template>
              <template v-else-if="column.key === 'tanggal_giro'">
                {{ row.tanggal_giro ? formatDate(row.tanggal_giro) : '-' }}
              </template>
              <template v-else-if="column.key === 'tanggal_cair'">
                {{ row.tanggal_cair ? formatDate(row.tanggal_cair) : '-' }}
              </template>
              <template v-else-if="column.key === 'nominal'">
                <span class="font-medium text-gray-900">{{ formatCurrency(row.nominal as any) }}</span>
              </template>
              <template v-else-if="column.key === 'status'">
                <span :class="getStatusBadgeClass(row.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">{{ row.status || '-' }}</span>
              </template>
              <template v-else-if="column.key === 'created_by'">
                {{ row.creator?.name || '-' }}
              </template>
              <template v-else-if="column.key === 'created_at'">
                {{ row.created_at ? formatDate(row.created_at) : '-' }}
              </template>
              <template v-else>
                {{ row[column.key] ?? '-' }}
              </template>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <button v-if="(row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin)" @click="$emit('action', { action: 'edit', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200" title="Edit">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button @click="$emit('action', { action: 'detail', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200" title="Detail">
                  <svg class="w-4 h-4 text-green-600" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 1H1V3H15V1Z" fill="currentColor" />
                    <path d="M11 5H1V7H6.52779C7.62643 5.7725 9.223 5 11 5Z" fill="currentColor" />
                    <path d="M5.34141 13C5.60482 13.7452 6.01127 14.4229 6.52779 15H1V13H5.34141Z" fill="currentColor" />
                    <path d="M5.34141 9C5.12031 9.62556 5 10.2987 5 11H1V9H5.34141Z" fill="currentColor" />
                    <path d="M15 11C15 11.7418 14.7981 12.4365 14.4462 13.032L15.9571 14.5429L14.5429 15.9571L13.032 14.4462C12.4365 14.7981 11.7418 15 11 15C8.79086 15 7 13.2091 7 11C7 8.79086 8.79086 7 11 7C13.2091 7 15 8.79086 15 11Z" fill="currentColor" />
                  </svg>
                </button>

                <button v-if="(row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin)" @click="$emit('action', { action: 'delete', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200" title="Hapus">
                  <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>

                <button v-if="['In Progress','Verified','Validated','Approved','Closed'].includes(row.status)" @click="$emit('action', { action: 'download', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200" title="Download">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>

                <button @click="$emit('action', { action: 'log', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200" title="Log Activity">
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!props.data || !props.data.length">
            <td :colspan="getTotalColumns()" class="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="props.pagination" class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <button @click="goToPage(props.pagination?.prev_page_url)" :disabled="!props.pagination?.prev_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', props.pagination?.prev_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">
          Previous
        </button>
        <template v-for="(link, index) in (props.pagination?.links || []).slice(1, -1)" :key="index">
          <button @click="goToPage(link.url)" :disabled="!link.url" :class="['w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200', link.active ? 'bg-black text-white' : link.url ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-gray-200 text-gray-400 cursor-not-allowed']" v-html="link.label"></button>
        </template>
        <button @click="goToPage(props.pagination?.next_page_url)" :disabled="!props.pagination?.next_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', props.pagination?.next_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">
          Next
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getStatusBadgeClass } from '@/lib/status';

interface Column { key: string; label: string; checked: boolean; sortable?: boolean }

const props = withDefaults(defineProps<{ data?: any[]; pagination?: any; selected?: number[]; columns?: Column[] }>(), { data: () => [], selected: () => [], columns: () => [] });
const emit = defineEmits(['select','action','paginate','add']);

const selectedIds = ref<number[]>([]);

const visibleColumns = computed(() => (props.columns || []).filter(c => c.checked));

// Current user and role
const page = usePage();
const currentUserId = computed<string | number | null>(() => (page.props as any)?.auth?.user?.id ?? null);
const isAdmin = computed<boolean>(() => ((page.props as any)?.auth?.user?.role?.name) === 'Admin');

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.created_by ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

const selectableRowIds = computed<number[]>(() => (props.data ?? [])
  .filter((row: any) => (row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin.value))
  .map((row: any) => row.id));
const showCheckbox = computed(() => selectableRowIds.value.length > 0);
const isAllSelected = computed(() => selectableRowIds.value.length > 0 && selectedIds.value.length === selectableRowIds.value.length);

watch(() => props.selected, (val) => { selectedIds.value = val ?? []; }, { immediate: true });
watch(selectedIds, (val) => { emit('select', val); });

function toggleSelectAll() {
  if (isAllSelected.value) selectedIds.value = []; else selectedIds.value = [...selectableRowIds.value];
}

function getColumnClass(key: string) { return key === 'nominal' ? 'text-right' : ''; }
function getCellClass(key: string) { return key === 'nominal' ? 'text-right font-medium text-gray-900' : 'text-[#101010]'; }
function getTotalColumns() { let total = visibleColumns.value.length; if (showCheckbox.value) total += 1; total += 1; return total; }
function goToPage(url: string) { emit('paginate', url); window.dispatchEvent(new CustomEvent('pagination-changed')); window.dispatchEvent(new CustomEvent('table-changed')); }

function formatDate(date: string) {
  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "2-digit",
  });
}

function formatCurrency(amount: number | string | null | undefined) {
  const num = typeof amount === 'string' ? Number(amount) : amount;
  if (num === null || num === undefined || isNaN(Number(num))) return '-';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(num));
}
</script>

<style scoped>
.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.sticky { position: sticky; z-index: 10; }

.alternating-row:nth-child(even) { background-color: #eff6f9; }
.alternating-row:nth-child(odd) { background-color: #ffffff; }
.alternating-row:nth-child(even):hover { background-color: #e0f2fe; }
.alternating-row:nth-child(odd):hover { background-color: #f8fafc; }
.alternating-row:nth-child(even) .action-cell { background-color: #eff6f9; }
.alternating-row:nth-child(odd) .action-cell { background-color: #ffffff; }
.alternating-row:nth-child(even):hover .action-cell { background-color: #e0f2fe; }
.alternating-row:nth-child(odd):hover .action-cell { background-color: #f8fafc; }

button:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
button:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
</style>
