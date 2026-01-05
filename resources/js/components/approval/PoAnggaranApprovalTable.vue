<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
            </th>
            <th v-for="column in visibleColumns" :key="column.key" class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap" :class="getColumnClass(column.key)">
              {{ column.label }}
            </th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in data" :key="row.id" class="alternating-row">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              <input type="checkbox" :value="row.id" v-model="selectedIds" :disabled="!isRowSelectable(row)" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" />
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
                <Tooltip v-if="row.status === 'Rejected' && row.rejection_reason">
                  <TooltipTrigger as-child>
                    <span :class="getStatusBadgeClass(row.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-help">{{ row.status || '-' }}</span>
                  </TooltipTrigger>
                  <TooltipContent>
                    <div class="max-w-xs bg-red-100 border border-red-300 rounded-lg p-3">
                      <p class="font-semibold text-red-800 mb-1">Alasan Penolakan</p>
                      <p class="text-sm text-red-600">{{ row.rejection_reason }}</p>
                    </div>
                  </TooltipContent>
                </Tooltip>
                <span v-else :class="getStatusBadgeClass(row.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">{{ row.status || '-' }}</span>
              </template>
              <template v-else-if="column.key === 'created_by'">
                {{ row.creator?.name || '-' }}
              </template>
              <template v-else-if="column.key === 'created_at'">
                {{ row.created_at ? formatDate(row.created_at) : '-' }}
              </template>
              <template v-else-if="column.key === 'note'">
                <div class="relative" @click.stop>
                  <div class="flex items-center">
                    <span class="inline-block max-w-[240px] truncate">
                      {{ truncateText(row.note) }}
                    </span>
                    <button
                      v-if="hasText(row.note)"
                      @click="toggleNote(row.id, $event)"
                      class="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none flex-shrink-0"
                      :title="activeNoteId === row.id ? 'Tutup catatan lengkap' : 'Lihat catatan lengkap'"
                    >
                      <svg
                        class="w-4 h-4 transform transition-transform duration-200"
                        :class="{ 'rotate-180': activeNoteId === row.id }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                      </svg>
                    </button>
                  </div>

                  <div
                    v-if="activeNoteId === row.id && hasText(row.note)"
                    class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm w-80"
                    style="min-width: 300px;"
                  >
                    <div class="flex items-start justify-between mb-2">
                      <h4 class="text-sm font-semibold text-gray-900">Catatan Lengkap:</h4>
                      <button
                        @click="closeNote()"
                        class="text-gray-400 hover:text-gray-600 transition-colors ml-2"
                        title="Tutup"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                          ></path>
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
                    <div
                      class="absolute -top-2 left-6 w-4 h-4 bg-white border-l border-t border-gray-200 transform rotate-45"
                    ></div>
                  </div>
                </div>
              </template>
              <template v-else>
                {{ row[column.key] ?? '-' }}
              </template>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Detail -->
                <button @click="$emit('action', { action: 'detail', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200" title="Detail">
                  <svg class="w-4 h-4 text-green-600" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 1H1V3H15V1Z" fill="currentColor" />
                    <path d="M11 5H1V7H6.52779C7.62643 5.7725 9.223 5 11 5Z" fill="currentColor" />
                    <path d="M5.34141 13C5.60482 13.7452 6.01127 14.4229 6.52779 15H1V13H5.34141Z" fill="currentColor" />
                    <path d="M5.34141 9C5.12031 9.62556 5 10.2987 5 11H1V9H5.34141Z" fill="currentColor" />
                    <path d="M15 11C15 11.7418 14.7981 12.4365 14.4462 13.032L15.9571 14.5429L14.5429 15.9571L13.032 14.4462C12.4365 14.7981 11.7418 15 11 15C8.79086 15 7 13.2091 7 11C7 8.79086 8.79086 7 11 7C13.2091 7 15 8.79086 15 11Z" fill="currentColor" />
                  </svg>
                </button>
                <!-- Download -->
                <button v-if="['In Progress','Verified','Validated','Approved','Closed'].includes(row.status)" @click="$emit('action', { action: 'download', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200" title="Download">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>
                <!-- Log -->
                <button @click="$emit('action', { action: 'log', row })" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200" title="Log Activity">
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!data || !data.length">
            <td :colspan="getTotalColumns()" class="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="pagination" class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <button @click="goToPage(pagination?.prev_page_url)" :disabled="!pagination?.prev_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', pagination?.prev_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">
          Previous
        </button>
        <template v-for="(link, index) in (pagination?.links || []).slice(1, -1)" :key="index">
          <button @click="goToPage(link.url)" :disabled="!link.url" :class="['w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200', link.active ? 'bg-black text-white' : link.url ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-gray-200 text-gray-400 cursor-not-allowed']" v-html="link.label"></button>
        </template>
        <button @click="goToPage(pagination?.next_page_url)" :disabled="!pagination?.next_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', pagination?.next_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">
          Next
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { getStatusBadgeClass } from '@/lib/status';

interface Column { key: string; label: string; checked: boolean; sortable?: boolean }

const props = withDefaults(defineProps<{ data?: any[]; pagination?: any; selected?: number[]; columns?: Column[]; currentRole?: string }>(), { data: () => [], selected: () => [], columns: () => [], currentRole: '' });
const emit = defineEmits(['select','action','paginate']);

const selectedIds = ref<number[]>([]);
const activeNoteId = ref<number | null>(null);
const visibleColumns = computed(() => (props.columns || []).filter(c => c.checked));

const page = usePage();
const userRole = computed(() => props.currentRole || ((page.props as any)?.auth?.user?.role?.name ?? ''));

const selectableRowIds = computed<number[]>(() => (props.data ?? [])
  .filter((row: any) => isRowSelectable(row))
  .map((row: any) => row.id));

const isAllSelected = computed(() => selectableRowIds.value.length > 0 && selectedIds.value.length === selectableRowIds.value.length);

watch(() => props.selected, (val) => { selectedIds.value = val ?? []; }, { immediate: true });
watch(selectedIds, (val) => { emit('select', val); });

function toggleSelectAll() { if (isAllSelected.value) selectedIds.value = []; else selectedIds.value = [...selectableRowIds.value]; }

function getColumnClass(key: string) { return key === 'nominal' ? 'text-right' : ''; }
function getCellClass(key: string) { return key === 'nominal' ? 'text-right font-medium text-gray-900' : 'text-[#101010]'; }
function getTotalColumns() { let total = visibleColumns.value.length; total += 1; total += 1; return total; }
function goToPage(url: string) { emit('paginate', url); }

function formatDate(date: string) { return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: '2-digit' }); }
function formatCurrency(amount: number | string | null | undefined) {
  const num = typeof amount === 'string' ? Number(amount) : amount;
  if (num === null || num === undefined || isNaN(Number(num))) return '-';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(num));
}

function truncateText(text: string, maxLength: number = 50) {
  if (!text) return '-';
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function hasText(text: string) {
  return !!text && text.trim() !== '';
}

function toggleNote(rowId: number, event: Event) {
  event.stopPropagation();
  if (activeNoteId.value === rowId) {
    activeNoteId.value = null;
  } else {
    activeNoteId.value = rowId;
  }
}

function closeNote() {
  activeNoteId.value = null;
}

function isRowSelectable(row: any): boolean {
  const role = userRole.value;
  const creatorRole = row?.creator?.role?.name;
  if (role === 'Kepala Toko') return row.status === 'In Progress' && creatorRole === 'Staff Toko';
  if (role === 'Kabag') return row.status === 'In Progress' && creatorRole === 'Staff Akunting & Finance';
  if (role === 'Kadiv') return row.status === 'Verified' || (row.status === 'In Progress' && creatorRole === 'Staff Digital Marketing');
  if (role === 'Direksi') return row.status === 'Validated' || row.status === 'Verified';
  return ['Admin'].includes(role) ? ['In Progress', 'Verified', 'Validated'].includes(row.status) : false;
}

// Actions are generic: table emits 'approve' and 'reject'. Page maps 'approve' to verify/validate/approve per role & status.
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
