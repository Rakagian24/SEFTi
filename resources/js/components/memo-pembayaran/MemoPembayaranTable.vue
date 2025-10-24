<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-center align-middle">
              <input
                v-model="selectAll"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                :disabled="!hasSelectableItems"
              />
            </th>
            <!-- Dynamic headers based on columns prop -->
            <th
              v-for="column in visibleColumns"
              :key="column.key"
              class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              {{ column.label }}
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
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
              <input
                v-if="
                  (row.status === 'Draft' || row.status === 'Rejected') &&
                  canSelectRow(row)
                "
                v-model="selectedItems"
                :value="row.id"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                @change="updateSelected"
              />
            </td>
            <!-- Dynamic cells based on visible columns -->
            <td
              v-for="column in visibleColumns"
              :key="column.key"
              class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]"
              :class="getCellClass(column.key)"
            >
              <template v-if="column.key === 'no_mb'">
                <span class="font-medium text-gray-900">{{ row.no_mb || "-" }}</span>
              </template>
              <template v-else-if="column.key === 'tanggal'">
                {{ row.tanggal ? formatDate(row.tanggal) : "-" }}
              </template>
              <template v-else-if="column.key === 'no_po'">
                <template v-if="getAllPurchaseOrders(row).length">
                  <div v-for="(po, idx) in getAllPurchaseOrders(row)" :key="idx">
                    {{ po.no_po || "-"
                    }}<span v-if="idx < getAllPurchaseOrders(row).length - 1">, </span>
                  </div>
                </template>
                <template v-else>-</template>
              </template>
              <template v-else-if="column.key === 'perihal'">
                <template v-if="getAllPurchaseOrders(row).length">
                  <div v-for="(po, idx) in getAllPurchaseOrders(row)" :key="idx">
                    {{ po.perihal?.nama_perihal || "-"
                    }}<span v-if="idx < getAllPurchaseOrders(row).length - 1">, </span>
                  </div>
                </template>
                <template v-else>-</template>
              </template>
              <template v-else-if="column.key === 'department'">
                {{ row.department?.name || "-" }}
              </template>
              <template v-else-if="column.key === 'supplier'">
                {{
                  row.supplier?.nama_supplier || getSupplierFromPurchaseOrders(row) || "-"
                }}
              </template>
              <template v-else-if="column.key === 'metode_pembayaran'">
                {{ row.metode_pembayaran || "-" }}
              </template>
              <template v-else-if="column.key === 'status'">
                <Tooltip v-if="row.status === 'Rejected' && row.rejection_reason">
                  <TooltipTrigger as-child>
                    <span
                      :class="getStatusBadgeClass(row.status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-help"
                    >
                      {{ row.status }}
                    </span>
                  </TooltipTrigger>
                  <TooltipContent>
                    <div class="max-w-xs">
                      <p class="font-medium text-red-800">Alasan Penolakan:</p>
                      <p class="text-sm text-red-700 mt-1">{{ row.rejection_reason }}</p>
                    </div>
                  </TooltipContent>
                </Tooltip>
                <span
                  v-else
                  :class="getStatusBadgeClass(row.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ row.status }}
                </span>
              </template>
              <template v-else-if="column.key === 'grand_total'">
                <span class="font-medium">{{ formatCurrency(row.grand_total) }}</span>
              </template>
              <template v-else-if="column.key === 'nama_rekening'">
                {{ row.nama_rekening || "-" }}
              </template>
              <template v-else-if="column.key === 'no_rekening'">
                {{ row.no_rekening || "-" }}
              </template>
              <template v-else-if="column.key === 'no_kartu_kredit'">
                {{ row.no_kartu_kredit || "-" }}
              </template>
              <template v-else-if="column.key === 'no_giro'">
                {{ row.no_giro || "-" }}
              </template>
              <template v-else-if="column.key === 'tanggal_giro'">
                {{ row.tanggal_giro ? formatDate(row.tanggal_giro) : "-" }}
              </template>
              <template v-else-if="column.key === 'tanggal_cair'">
                {{ row.tanggal_cair ? formatDate(row.tanggal_cair) : "-" }}
              </template>
              <template v-else-if="column.key === 'keterangan'">
                {{ row.keterangan || "-" }}
              </template>
              <template v-else-if="column.key === 'total'">
                {{ formatCurrency(row.total) }}
              </template>
              <template v-else-if="column.key === 'diskon'">
                {{ formatCurrency(row.diskon) }}
              </template>
              <template v-else-if="column.key === 'ppn'">
                {{ row.ppn || "-" }}
              </template>
              <template v-else-if="column.key === 'ppn_nominal'">
                {{ formatCurrency(row.ppn_nominal) }}
              </template>
              <template v-else-if="column.key === 'pph_nominal'">
                {{ formatCurrency(row.pph_nominal) }}
              </template>
              <template v-else-if="column.key === 'created_by'">
                {{ row.creator?.name || "-" }}
              </template>
              <template v-else-if="column.key === 'created_at'">
                {{ row.created_at ? formatDate(row.created_at) : "-" }}
              </template>
              <template v-else>
                {{ getColumnValue(row, column.key) }}
              </template>
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell"
            >
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit -->
                <button
                  v-if="canEditRow(row)"
                  @click="handleAction('edit', row)"
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

                <!-- Delete -->
                <button
                  v-if="canDeleteRow(row)"
                  @click="handleAction('delete', row)"
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
                  @click="handleAction('detail', row)"
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

                <!-- Preview -->
                <button
                  @click="handleAction('preview', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-indigo-50 hover:bg-indigo-100 transition-colors duration-200"
                  title="Preview"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="w-4 h-4 text-indigo-600"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"
                    />
                  </svg>
                </button>

                <!-- Download -->
                <button
                  v-if="row.status !== 'Draft' && row.status !== 'Rejected'"
                  @click="handleAction('download', row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200"
                  title="Unduh"
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
                  @click="handleAction('log', row)"
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

    <!-- Confirmation Dialog -->
    <ConfirmDialog
      :show="showConfirm"
      :message="confirmMessage"
      @confirm="onConfirmDelete"
      @cancel="onCancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatCurrency } from "@/lib/currencyUtils";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";
import { Tooltip, TooltipContent, TooltipTrigger } from "../ui/tooltip";

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const props = defineProps<{
  data: any[];
  pagination: any;
  selected: number[];
  columns?: Column[];
}>();

const emit = defineEmits<{
  select: [selected: number[]];
  action: [payload: { action: string; row: any }];
  paginate: [url: string];
  "update:columns": [columns: Column[]];
}>();

const selectedItems = ref<number[]>([]);
const showConfirm = ref(false);
const confirmTargetId = ref<number | null>(null);
const confirmMessage = ref<string>(
  "Apakah Anda yakin ingin membatalkan memo pembayaran ini?"
);

// Watch for changes in selected prop
watch(
  () => props.selected,
  (newSelected) => {
    selectedItems.value = [...newSelected];
  },
  { immediate: true }
);

const hasSelectableItems = computed(() =>
  props.data.some(
    (item) =>
      (item.status === "Draft" || item.status === "Rejected") && canSelectRow(item)
  )
);

const selectAll = computed({
  get: () => {
    const selectableItems = props.data.filter(
      (item) =>
        (item.status === "Draft" || item.status === "Rejected") && canSelectRow(item)
    );
    return (
      selectableItems.length > 0 &&
      selectableItems.every((item) => selectedItems.value.includes(item.id))
    );
  },
  set: (value) => {
    const selectableItemIds = props.data
      .filter(
        (item) =>
          (item.status === "Draft" || item.status === "Rejected") && canSelectRow(item)
      )
      .map((item) => item.id);
    if (value) {
      selectedItems.value = [...new Set([...selectedItems.value, ...selectableItemIds])];
    } else {
      selectedItems.value = selectedItems.value.filter(
        (id) => !selectableItemIds.includes(id)
      );
    }
    updateSelected();
  },
});

// Column visibility logic
const visibleColumns = computed(() => {
  if (!props.columns) {
    // Default columns if none provided
    return [
      { key: "no_mb", label: "No. MB", checked: true, sortable: false },
      { key: "no_po", label: "No. PO", checked: true, sortable: false },
      { key: "supplier", label: "Supplier", checked: true, sortable: false },
      { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
      { key: "status", label: "Status", checked: true, sortable: true },
    ];
  }
  return props.columns.filter((col) => col.checked);
});

// Determine current user id for creator checks (Rejected can only be edited by creator or Admin)
const page = usePage();
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props.auth as any)?.user?.id;
  return id ?? null;
});

// Check if current user is Admin
const isAdmin = computed<boolean>(() => {
  const userRole = (page.props.auth as any)?.user?.role?.name;
  return userRole === "Admin";
});

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

// Check if user can edit this row
function canEditRow(row: any) {
  if (row.status === "Draft") {
    return isCreatorRow(row);
  }
  if (row.status === "Rejected") {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

// Check if user can delete this row
function canDeleteRow(row: any) {
  if (row.status === "Draft" || row.status === "Rejected") {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

// Check if user can select this row (for sending)
function canSelectRow(row: any) {
  if (row.status === "Draft" || row.status === "Rejected") {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

// Header checkbox uses v-model on computed `selectAll` which already
// handles selecting/unselecting all draft rows via its setter.

function updateSelected() {
  emit("select", selectedItems.value);
}

// Keep selection valid when data changes (e.g., pagination/filter)
watch(
  () => props.data,
  (rows) => {
    const validIds = new Set(
      (rows || [])
        .filter(
          (r: any) => (r.status === "Draft" || r.status === "Rejected") && canSelectRow(r)
        )
        .map((r: any) => r.id)
    );
    selectedItems.value = selectedItems.value.filter((id) => validIds.has(id));
    updateSelected();
  }
);

function handleAction(action: string, row: any) {
  if (action === "delete") {
    confirmTargetId.value = row.id;
    confirmMessage.value = `Apakah Anda yakin ingin membatalkan memo pembayaran ${
      row.no_mb || "ini"
    }?`;
    showConfirm.value = true;
  } else {
    emit("action", { action, row });
  }
}

function onConfirmDelete() {
  if (confirmTargetId.value != null) {
    const row = props.data.find((item) => item.id === confirmTargetId.value);
    if (row) {
      emit("action", { action: "delete", row });
    }
  }
  confirmTargetId.value = null;
  showConfirm.value = false;
}

function onCancelDelete() {
  confirmTargetId.value = null;
  showConfirm.value = false;
}

function handlePagination(url?: string | null) {
  if (!url) return;
  emit("paginate", url);
}

function formatDate(date: string) {
  if (!date) return "-";
  const d = new Date(date);
  return d.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "2-digit",
  });
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getAllPurchaseOrders(row: any) {
  if (
    !row.purchase_orders ||
    !Array.isArray(row.purchase_orders) ||
    row.purchase_orders.length === 0
  ) {
    // Fallback to single purchase_order if purchase_orders is not available
    if (row.purchase_order) {
      return [row.purchase_order];
    }
    return [];
  }
  return row.purchase_orders;
}

function getSupplierFromPurchaseOrders(row: any) {
  const purchaseOrders = getAllPurchaseOrders(row);

  // Try different possible supplier data structures from Purchase Orders
  for (const po of purchaseOrders) {
    // Check if supplier data exists in different possible structures
    if (po.supplier?.name) {
      return po.supplier.name;
    }
    if (po.supplier?.nama) {
      return po.supplier.nama;
    }
    if (po.supplier_name) {
      return po.supplier_name;
    }
    if (po.supplier) {
      return po.supplier;
    }
  }

  // Fallback: check if supplier data exists directly on the memo pembayaran row
  if (row.supplier?.name) {
    return row.supplier.name;
  }
  if (row.supplier?.nama) {
    return row.supplier.nama;
  }
  if (row.supplier_name) {
    return row.supplier_name;
  }
  if (row.supplier) {
    return row.supplier;
  }

  return null;
}

function getCellClass(key: string) {
  if (
    key === "grand_total" ||
    key === "total" ||
    key === "diskon" ||
    key === "ppn_nominal" ||
    key === "pph_nominal"
  ) {
    return "text-right font-medium";
  }
  return "";
}

function getColumnValue(row: any, key: string) {
  // Generic fallback for any column not explicitly handled
  return row[key] || "-";
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

/* Pagination styling kept consistent with other modules */
</style>
