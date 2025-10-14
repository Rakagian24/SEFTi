<script setup lang="ts">
import { computed, ref, watch } from "vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

const props = defineProps<{ data: any[] }>();
const emit = defineEmits<{
  select: [ids: number[]];
  action: [payload: { action: string; row: any }];
}>();

const selectedIds = ref<number[]>([]);
const showConfirm = ref(false);
const confirmId = ref<number | null>(null);

const hasSelectable = computed(() => props.data.some(r => r.status === 'Draft'));

const selectAll = computed({
  get() {
    const selectable = props.data.filter(r => r.status === 'Draft').map(r => r.id);
    return selectable.length > 0 && selectable.every(id => selectedIds.value.includes(id));
  },
  set(val: boolean) {
    if (!val) {
      selectedIds.value = [];
    } else {
      selectedIds.value = props.data.filter(r => r.status === 'Draft').map(r => r.id);
    }
    emit("select", selectedIds.value);
  }
});

watch(() => props.data, () => {
  const valid = new Set(props.data.filter(r => r.status === 'Draft').map(r => r.id));
  selectedIds.value = selectedIds.value.filter(id => valid.has(id));
  emit("select", selectedIds.value);
});

function onAction(action: string, row: any) {
  if (action === 'cancel') {
    confirmId.value = row.id;
    showConfirm.value = true;
  } else {
    emit('action', { action, row });
  }
}

function onConfirm() {
  if (confirmId.value != null) {
    const row = props.data.find(r => r.id === confirmId.value);
    if (row) emit('action', { action: 'cancel', row });
  }
  confirmId.value = null;
  showConfirm.value = false;
}

function onCancel() {
  confirmId.value = null;
  showConfirm.value = false;
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-center align-middle">
              <input 
                type="checkbox" 
                v-model="selectAll" 
                :disabled="!hasSelectable"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              />
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. BPB</th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. PO</th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. PV</th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal</th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in props.data" :key="row.id" class="alternating-row">
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <input
                type="checkbox"
                :disabled="row.status !== 'Draft'"
                :value="row.id"
                v-model="selectedIds"
                @change="emit('select', selectedIds)"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              />
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              <span class="font-medium text-gray-900">{{ row.no_bpb || '-' }}</span>
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.purchase_order?.no_po || '-' }}</td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.payment_voucher?.no_pv || '-' }}</td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.tanggal || '-' }}</td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="{
                  'bg-gray-100 text-gray-700': row.status==='Draft',
                  'bg-yellow-100 text-yellow-700': row.status==='In Progress',
                  'bg-green-100 text-green-700': row.status==='Approved',
                  'bg-red-100 text-red-700': row.status==='Canceled' || row.status==='Rejected'
                }"
              >{{ row.status }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit -->
                <button 
                  :disabled="row.status!=='Draft'" 
                  @click="onAction('edit', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                  title="Edit"
                >
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 01-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <!-- Cancel -->
                <button 
                  :disabled="row.status!=='Draft'" 
                  @click="onAction('cancel', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                  title="Batalkan"
                >
                  <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>

                <!-- Detail -->
                <button 
                  @click="onAction('detail', row)"
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
                  :disabled="row.status==='Canceled'" 
                  @click="onAction('download', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                  title="Unduh"
                >
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>

                <!-- Log -->
                <button 
                  @click="onAction('log', row)"
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
        </tbody>
      </table>
    </div>

    <ConfirmDialog
      v-if="showConfirm"
      :show="showConfirm"
      :message="'Apakah Anda yakin ingin membatalkan dokumen ini?'"
      @confirm="onConfirm"
      @cancel="onCancel"
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
</style>