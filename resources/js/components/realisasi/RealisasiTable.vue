<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              v-if="hasSelectableItems"
              class="px-6 py-4 text-center align-middle"
            >
              <input
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                :checked="selectAll"
                @change="toggleSelectAll"
              />
            </th>
            <th
              v-for="c in visibleColumns"
              :key="c.key"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              {{ c.label }}
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]"
            >
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in data" :key="row.id" class="alternating-row">
            <td
              v-if="hasSelectableItems"
              class="px-6 py-4 text-center align-middle whitespace-nowrap"
            >
              <input
                v-if="canSelectRow(row)"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                :checked="selected.includes(row.id)"
                @change="toggle(row.id, ($event.target as HTMLInputElement).checked)"
              />
            </td>
            <td
              v-for="c in visibleColumns"
              :key="c.key"
              class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]"
            >
              <template v-if="c.key === 'department'">
                {{ row.department?.name || "-" }}
              </template>
              <template v-else-if="c.key === 'no_po_anggaran'">
                {{
                  // Eloquent biasanya menyerialisasi relasi poAnggaran() sebagai key "po_anggaran"
                  row.po_anggaran?.no_po_anggaran ||
                  row.poAnggaran?.no_po_anggaran ||
                  row.no_po_anggaran ||
                  "-"
                }}
              </template>
              <template v-else-if="c.key === 'tanggal' || c.key === 'created_at'">
                {{ row[c.key] ? formatDate(row[c.key]) : "-" }}
              </template>
              <template v-else-if="c.key === 'bisnis_partner'">
                {{ row.bisnis_partner?.nama_bp || row.bisnisPartner?.nama_bp || "-" }}
              </template>
              <template v-else-if="c.key === 'sisa'">
                {{ formatCurrency((Number(row.total_anggaran) || 0) - (Number(row.total_realisasi) || 0)) }}
              </template>
              <template v-else-if="c.key === 'total_anggaran' || c.key === 'total_realisasi'">
                {{ formatCurrency(row[c.key]) }}
              </template>
              <template v-else-if="c.key === 'status'">
                <span
                  v-if="row.status === 'Closed' && row.closed_reason"
                  :class="getStatusBadgeClass(row.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-help"
                  :title="row.closed_reason"
                >
                  {{ row.status }}
                </span>
                <span
                  v-else
                  :class="getStatusBadgeClass(row.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ row.status }}
                </span>
              </template>
              <template v-else>
                {{ row[c.key] || "-" }}
              </template>
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell"
            >
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit -->
                <button
                  v-if="canEditRow(row)"
                  @click="$emit('action', { action: 'edit', row })"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200"
                  :title="row.status === 'Rejected' ? 'Perbaiki' : 'Edit'"
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
                      d="M11 5H6a2 2 0 01-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>

                <!-- Close -->
                <button
                  v-if="canCloseRow(row)"
                  @click="$emit('action', { action: 'close', row })"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-yellow-50 hover:bg-yellow-100 transition-colors duration-200"
                  title="Tutup (Closed)"
                >
                  <svg
                    class="w-4 h-4 text-yellow-600"
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

                <!-- Delete/Cancel -->
                <button
                  v-if="canDeleteRow(row)"
                  @click="askCancelRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Batalkan"
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

                <!-- Detail -->
                <button
                  v-if="
                    (row.status === 'Draft' && !isCreatorRow(row)) ||
                    (row.status !== 'Draft' &&
                      (row.status !== 'Rejected' ||
                        (row.status === 'Rejected' && !isCreatorRow(row))))
                  "
                  @click="$emit('action', { action: 'detail', row })"
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

                <!-- Download PDF -->
                <button
                  v-if="['In Progress', 'Verified', 'Approved'].includes(row.status)"
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

                <!-- Log -->
                <button
                  @click="$emit('action', { action: 'log', row })"
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
    <div
      v-if="pagination"
      class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
    >
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="handlePagination(pagination?.prev_page_url)"
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

        <!-- Page Numbers -->
        <template
          v-for="(link, index) in (pagination?.links || []).slice(1, -1)"
          :key="index"
        >
          <button
            @click="handlePagination(link.url)"
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
          @click="handlePagination(pagination?.next_page_url)"
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

  <ConfirmDialog
    :show="showConfirmCancel"
    message="Apakah Anda yakin ingin membatalkan Realisasi ini?"
    @confirm="onConfirmCancel"
    @cancel="onCancelDialog"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

const props = defineProps<{
  data: any[];
  pagination: any;
  selected: number[];
  columns: any[];
}>();

const emit = defineEmits(['select', 'action', 'paginate', 'add', 'update:entries-per-page']);

const visibleColumns = computed(() => {
  return props.columns.filter((col) => col.checked);
});

const showConfirmCancel = ref(false);
const cancelRow = ref<any | null>(null);

const page = usePage();
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props.auth as any)?.user?.id;
  return id ?? null;
});

const isAdmin = computed<boolean>(() => {
  const userRole = (page.props.auth as any)?.user?.role?.name;
  return userRole === 'Admin';
});

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by ?? row?.created_by_id ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function canSelectRow(row: any) {
  if (row.status === 'Draft' || row.status === 'Rejected') {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

function canEditRow(row: any) {
  // Ikuti pola PurchaseOrder: Draft hanya creator; Rejected creator atau Admin
  if (row.status === 'Draft') {
    return isCreatorRow(row);
  }
  if (row.status === 'Rejected') {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

function canDeleteRow(row: any) {
  // Ikuti pola PurchaseOrder: Draft/Rejected bisa dihapus oleh creator atau Admin
  if (row.status === 'Draft' || row.status === 'Rejected') {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

function askCancelRow(row: any) {
  cancelRow.value = row;
  showConfirmCancel.value = true;
}

function onConfirmCancel() {
  if (!cancelRow.value) {
    showConfirmCancel.value = false;
    return;
  }
  emit('action', { action: 'delete', row: cancelRow.value });
  showConfirmCancel.value = false;
  cancelRow.value = null;
}

function onCancelDialog() {
  showConfirmCancel.value = false;
  cancelRow.value = null;
}

function canCloseRow(row: any) {
  if (row.status === 'Approved') {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

const hasSelectableItems = computed(() => {
  return props.data.some((item) => canSelectRow(item));
});

const selectAll = computed(() => {
  const selectableItems = props.data.filter((item) => canSelectRow(item));
  return (
    selectableItems.length > 0 &&
    selectableItems.every((item) => props.selected.includes(item.id))
  );
});

function toggleSelectAll(event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  const draftItemIds = props.data
    .filter((item) => canSelectRow(item))
    .map((item) => item.id);

  if (checked) {
    const newSelected = [...new Set([...props.selected, ...draftItemIds])];
    emit('select', newSelected);
  } else {
    const newSelected = props.selected.filter((id) => !draftItemIds.includes(id));
    emit('select', newSelected);
  }
}

function toggle(id: number, checked: boolean) {
  const set = new Set(props.selected);
  if (checked) set.add(id);
  else set.delete(id);
  emit('select', Array.from(set));
}

function handlePagination(url?: string | null) {
  if (!url) return;
  emit('paginate', url);
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

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
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
