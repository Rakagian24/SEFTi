<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
// No internal ConfirmDialog; parent handles confirmations
import { getStatusBadgeClass } from "@/lib/status";

const props = defineProps<{ data: any[]; pagination?: any }>();
const emit = defineEmits<{
  select: [ids: number[]];
  action: [payload: { action: string; row: any }];
  paginate: [url: string];
}>();

const selectedIds = ref<number[]>([]);

// Permissions like other modules
const page = usePage();
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props as any)?.auth?.user?.id;
  return id ?? null;
});
const isAdmin = computed<boolean>(() => {
  const role = (page.props as any)?.auth?.user?.role?.name;
  return String(role || '').toLowerCase() === 'admin';
});

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.created_by ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function canEditRow(row: any) {
  if (row.status === 'Draft') return isCreatorRow(row);
  if (row.status === 'Rejected') return isCreatorRow(row) || isAdmin.value;
  return false;
}

function canCancelRow(row: any) {
  if (row.status === 'Draft' || row.status === 'Rejected') return isCreatorRow(row) || isAdmin.value;
  return false;
}

function canSelectRow(row: any) {
  if (row.status === 'Draft' || row.status === 'Rejected') return isCreatorRow(row) || isAdmin.value;
  return false;
}

const hasSelectable = computed(() => props.data.some(r => (r.status === 'Draft' || r.status === 'Rejected') && canSelectRow(r)));

const selectAll = computed({
  get() {
    const selectable = props.data.filter(r => (r.status === 'Draft' || r.status === 'Rejected') && canSelectRow(r)).map(r => r.id);
    return selectable.length > 0 && selectable.every(id => selectedIds.value.includes(id));
  },
  set(val: boolean) {
    if (!val) {
      selectedIds.value = [];
    } else {
      selectedIds.value = props.data.filter(r => (r.status === 'Draft' || r.status === 'Rejected') && canSelectRow(r)).map(r => r.id);
    }
    emit("select", selectedIds.value);
  }
});

watch(() => props.data, () => {
  const valid = new Set(props.data.filter(r => (r.status === 'Draft' || r.status === 'Rejected') && canSelectRow(r)).map(r => r.id));
  selectedIds.value = selectedIds.value.filter(id => valid.has(id));
  emit("select", selectedIds.value);
});

function onAction(action: string, row: any) {
  // Emit directly; parent will show confirmation dialogs
  emit('action', { action, row });
}

function goToPage(url: string) {
  if (url) emit("paginate", url);
}

function formatDate(date: string) {
  if (!date) return "-";
  const d = new Date(date);
  return d.toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "2-digit" });
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
                :disabled="!canSelectRow(row)"
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
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">{{ row.tanggal ? formatDate(row.tanggal) : '-' }}</td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusBadgeClass(row.status)"
              >{{ row.status }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit -->
                <button
                  v-if="canEditRow(row)"
                  @click="onAction('edit', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                  :title="row.status === 'Rejected' ? 'Perbaiki' : 'Edit'"
                >
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 01-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <!-- Cancel -->
                <button
                  v-if="canCancelRow(row)"
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
                  v-if="
                    (row.status === 'Draft' && !isCreatorRow(row)) ||
                    (row.status !== 'Draft' && (row.status !== 'Rejected' || (row.status === 'Rejected' && !isCreatorRow(row))))
                  "
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
                  v-if="row.status !== 'Draft' && row.status !== 'Rejected'"
                  @click="onAction('download', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200"
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

    <!-- Confirmation handled by parent -->
  </div>

  <!-- Pagination -->
  <div
    v-if="props.pagination"
    class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
  >
    <nav class="flex items-center space-x-2" aria-label="Pagination">
      <!-- Previous Button -->
      <button
        @click="goToPage(props.pagination?.prev_page_url)"
        :disabled="!props.pagination?.prev_page_url"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
          props.pagination?.prev_page_url
            ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
            : 'text-gray-400 cursor-not-allowed',
        ]"
      >
        Previous
      </button>

      <!-- Page Numbers -->
      <template
        v-for="(link, index) in (props.pagination?.links || []).slice(1, -1)"
        :key="index"
      >
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
        @click="goToPage(props.pagination?.next_page_url)"
        :disabled="!props.pagination?.next_page_url"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
          props.pagination?.next_page_url
            ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
            : 'text-gray-400 cursor-not-allowed',
        ]"
      >
        Next
      </button>
    </nav>
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
