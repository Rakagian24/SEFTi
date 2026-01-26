<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-center align-middle">
              <input
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                :disabled="selectableRowIds.length === 0"
              />
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. Realisasi</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Departemen</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Metode Pembayaran</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Bisnis Partner</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Total Anggaran</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Total Realisasi</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Sisa</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in data" :key="row.id" class="alternating-row">
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <input
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                :value="row.id"
                v-model="selectedIds"
                :disabled="!isRowSelectable(row)"
              />
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ row.no_realisasi || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ row.tanggal ? formatDate(row.tanggal) : '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ row.department?.name || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ row.metode_pembayaran || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ row.bisnis_partner?.nama_bp || row.bisnisPartner?.nama_bp || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.total_anggaran) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency(row.total_realisasi) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">{{ formatCurrency((Number(row.total_anggaran) || 0) - (Number(row.total_realisasi) || 0)) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span :class="getStatusBadgeClass(row.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                {{ row.status || '-' }}
              </span>
            </td>
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
                <!-- Download PDF -->
                <button
                  @click="$emit('action', { action: 'download', row })"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200"
                  title="Unduh PDF"
                >
                  <svg
                    class="w-4 h-4 text-purple-600"
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
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!data || !data.length">
            <td :colspan="10" class="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getStatusBadgeClass } from '@/lib/status';

const props = withDefaults(defineProps<{ data?: any[]; selected?: number[]; currentRole?: string }>(), { data: () => [], selected: () => [], currentRole: '' });
const emit = defineEmits(['select','action']);

const selectedIds = ref<number[]>([]);

const page = usePage();
const userRole = computed(() => props.currentRole || ((page.props as any)?.auth?.user?.role?.name ?? ''));

const selectableRowIds = computed<number[]>(() => (props.data ?? [])
  .filter((row: any) => isRowSelectable(row))
  .map((row: any) => row.id));

const isAllSelected = computed(() => selectableRowIds.value.length > 0 && selectedIds.value.length === selectableRowIds.value.length);

watch(() => props.selected, (val) => { selectedIds.value = val ?? []; }, { immediate: true });
watch(selectedIds, (val) => { emit('select', val); });

function toggleSelectAll() { if (isAllSelected.value) selectedIds.value = []; else selectedIds.value = [...selectableRowIds.value]; }

function isRowSelectable(row: any): boolean {
  const role = userRole.value;
  const status = row.status;

  if (role === 'Kepala Toko') {
    return status === 'In Progress';
  }

  if (role === 'Kabag') {
    return ['In Progress', 'Verified'].includes(status);
  }

  if (role === 'Kadiv') {
    return ['In Progress', 'Verified'].includes(status);
  }

  // Direksi dapat memilih dokumen yang sudah siap untuk approval akhir
  if (role === 'Direksi') {
    return ['Verified', 'Validated'].includes(status);
  }

  // Admin mengikuti aturan umum: bisa pilih In Progress / Verified
  return role === 'Admin' ? ['In Progress', 'Verified'].includes(status) : false;
}

function formatCurrency(amount: number | string | null | undefined) {
  if (amount === null || amount === undefined || amount === '') return '-';
  const numeric = typeof amount === 'string' ? Number(amount) : amount;
  if (Number.isNaN(numeric)) return '-';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(numeric as number);
}

function formatDate(date: string) {
  if (!date) return '-';
  const d = new Date(date);
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: '2-digit',
  });
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
