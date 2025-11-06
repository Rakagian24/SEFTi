<template>
  <!-- Empty State -->
  <EmptyState
    v-if="!props.data || props.data.length === 0"
    title="Tidak ada dokumen Payment Voucher"
    description="Tidak ada Payment Voucher yang menunggu persetujuan."
    icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
    :show-action="false"
  />

  <!-- Table with data -->
  <div v-else class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              v-if="showCheckbox"
              class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
            </th>
            <!-- Dynamic headers based on columns prop -->
            <th
              v-for="column in visibleColumns"
              :key="column.key"
              class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
              :class="getColumnClass(column.key)"
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
          <tr v-for="row in props.data" :key="row.id" class="alternating-row">
            <td
              v-if="showCheckbox"
              class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]"
            >
              <input
                v-if="
                  (props.selectableStatuses ?? []).includes(row.status) &&
                  props.isRowSelectable(row)
                "
                type="checkbox"
                :value="row.id"
                v-model="selectedIds"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
            </td>
            <!-- Dynamic data cells based on columns prop -->
            <td
              v-for="column in visibleColumns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap text-sm"
              :class="getCellClass(column.key)"
            >
              <template v-if="column.key === 'no_pv'">
                <span class="font-medium text-gray-900">{{ row.no_pv || "-" }}</span>
              </template>
              <template v-else-if="column.key === 'tanggal'">
                {{ row.tanggal ? formatDate(row.tanggal) : "-" }}
              </template>
              <template v-else-if="column.key === 'reference_number'">
                <span class="font-medium text-gray-900">{{ (row as any)?.reference_number || '-' }}</span>
              </template>
              <template v-else-if="column.key === 'no_po'">
                <div class="text-sm">
                  <template v-if="getAllPurchaseOrders(row).length">
                    <div v-for="(po, idx) in getAllPurchaseOrders(row)" :key="idx">
                      {{ po.no_po || "-"
                      }}<span v-if="idx < getAllPurchaseOrders(row).length - 1">, </span>
                    </div>
                  </template>
                  <template v-else>-</template>
                </div>
              </template>
              <template v-else-if="column.key === 'perihal'">
                <div class="text-sm">
                  <template v-if="getAllPurchaseOrders(row).length">
                    <div v-for="(po, idx) in getAllPurchaseOrders(row)" :key="idx">
                      {{ po.perihal?.nama || "-"
                      }}<span v-if="idx < getAllPurchaseOrders(row).length - 1">, </span>
                    </div>
                  </template>
                  <template v-else>-</template>
                </div>
              </template>
              <template v-else-if="column.key === 'department'">
                {{ row.department?.name || "-" }}
              </template>
              <template v-else-if="column.key === 'supplier'">
                {{ formatSupplier(row) || "-" }}
              </template>
              <template v-else-if="column.key === 'metode_pembayaran'">
                {{ row.metode_pembayaran || "-" }}
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
                <div class="max-w-xs truncate" :title="row.keterangan">
                  {{ row.keterangan || "-" }}
                </div>
              </template>
              <template v-else-if="column.key === 'total'">
                <span class="font-medium">{{ formatCurrency(row.total) }}</span>
              </template>
              <template v-else-if="column.key === 'diskon'">
                <span class="font-medium">{{ formatCurrency(row.diskon) }}</span>
              </template>
              <template v-else-if="column.key === 'ppn'">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    row.ppn ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                  ]"
                >
                  {{ row.ppn ? "Ya" : "Tidak" }}
                </span>
              </template>
              <template v-else-if="column.key === 'ppn_nominal'">
                <span class="font-medium">{{ formatCurrency(row.ppn_nominal) }}</span>
              </template>
              <template v-else-if="column.key === 'pph_nominal'">
                <span class="font-medium">{{ formatCurrency(row.pph_nominal) }}</span>
              </template>
              <template v-else-if="column.key === 'grand_total'">
                <span class="font-medium text-gray-900">{{
                  formatCurrency(row.grand_total)
                }}</span>
              </template>
              <template v-else-if="column.key === 'kelengkapan_dokumen'">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    (row as any)?.kelengkapan_dokumen ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                  ]"
                >
                  {{ (row as any)?.kelengkapan_dokumen ? 'Lengkap' : 'Tidak Lengkap' }}
                </span>
              </template>
              <template v-else-if="column.key === 'status'">
                <Tooltip v-if="row.status === 'Rejected' && row.rejection_reason">
                  <TooltipTrigger as-child>
                    <span
                      :class="getStatusBadgeClass(row.status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-help"
                    >
                      {{ getStatusText(row.status) }}
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
                  {{ getStatusText(row.status) }}
                </span>
              </template>
              <template v-else-if="column.key === 'created_by'">
                {{ row.creator?.name || "-" }}
              </template>
              <template v-else-if="column.key === 'created_at'">
                {{ row.created_at ? formatDate(row.created_at) : "-" }}
              </template>
              <template v-else>
                {{ row[column.key] || "-" }}
              </template>
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell"
            >
              <div class="flex items-center justify-center space-x-2">
                <!-- Detail Button -->
                <button
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

                <!-- Download Button -->
                <button
                  v-if="
                    ['In Progress', 'Approved', 'Verified', 'Validated'].includes(
                      row.status
                    )
                  "
                  @click="$emit('action', { action: 'download', row })"
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

                <!-- Log Activity Button -->
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
          <tr v-if="!props.data || !props.data.length">
            <td
              :colspan="getTotalColumns()"
              class="px-6 py-8 text-center text-sm text-gray-500"
            >
              Tidak ada data
            </td>
          </tr>
        </tbody>
      </table>
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
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import EmptyState from "../ui/EmptyState.vue";
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";
import { Tooltip, TooltipContent, TooltipTrigger } from "../ui/tooltip";

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const props = withDefaults(
  defineProps<{
    data?: any[];
    loading?: boolean;
    selected?: number[];
    pagination?: any;
    columns?: Column[];
    selectableStatuses?: string[];
    isRowSelectable?: (row: any) => boolean;
  }>(),
  {
    data: () => [],
    selected: () => [],
    columns: () => [],
    selectableStatuses: () => ["In Progress"],
    isRowSelectable: () => true,
  }
);
const emit = defineEmits(["select", "action", "paginate"]);
const selectedIds = ref<number[]>([]);

// Filter visible columns based on checked status
const visibleColumns = computed(() => {
  if (!props.columns || props.columns.length === 0) {
    // Default columns if none provided
    return [
      { key: "no_pv", label: "No. MB", checked: true, sortable: false },
      { key: "reference_number", label: "Nomor Referensi Dokumen", checked: true, sortable: false },
      { key: "supplier", label: "Supplier", checked: true, sortable: false },
      { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
      { key: "status", label: "Status", checked: true, sortable: true },
    ];
  }
  return (props.columns || []).filter((column) => column.checked);
});

const showCheckbox = computed(() =>
  (props.data ?? []).some((row) => (props.selectableStatuses ?? []).includes(row.status))
);

// Only rows that match selectableStatuses AND pass isRowSelectable function are selectable
const selectableRowIds = computed<number[]>(() =>
  (props.data ?? [])
    .filter(
      (row: any) =>
        (props.selectableStatuses ?? []).includes(row.status) &&
        props.isRowSelectable(row)
    )
    .map((row: any) => row.id)
);

const isAllSelected = computed<boolean>(
  () =>
    selectableRowIds.value.length > 0 &&
    selectedIds.value.length === selectableRowIds.value.length
);

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = [...selectableRowIds.value];
  }
}

watch(
  () => props.selected,
  (val) => {
    selectedIds.value = val ?? [];
  },
  { immediate: true }
);
watch(selectedIds, (val) => {
  emit("select", val);
});

function formatDate(date: string) {
  return new Date(date).toLocaleDateString("id-ID");
}

function formatCurrency(amount: number) {
  if (amount === null || amount === undefined) return "-";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

function getColumnClass(key: string) {
  // Add specific styling for certain columns
  const rightAlignColumns = [
    "total",
    "diskon",
    "ppn_nominal",
    "pph_nominal",
    "grand_total",
  ];
  if (rightAlignColumns.includes(key)) {
    return "text-right";
  }
  return "";
}

function getCellClass(key: string) {
  // Add specific styling for certain cells
  const rightAlignColumns = [
    "total",
    "diskon",
    "ppn_nominal",
    "pph_nominal",
    "grand_total",
  ];
  if (rightAlignColumns.includes(key)) {
    return "text-right font-medium text-gray-900";
  }
  if (key === "no_pv" || key === 'reference_number') {
    return "font-medium text-gray-900";
  }
  if (key === "keterangan") {
    return "text-left text-[#101010]";
  }
  return "text-[#101010]";
}

function getTotalColumns() {
  let total = visibleColumns.value.length;
  if (showCheckbox.value) total += 1;
  total += 1; // Action column
  return total;
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusText(status: string) {
  // Return status as is (from database)
  return status;
}

function goToPage(url: string) {
  emit("paginate", url);
  window.dispatchEvent(new CustomEvent("pagination-changed"));
  window.dispatchEvent(new CustomEvent("table-changed"));
}

function getAllPurchaseOrders(row: any) {
  // Check for many-to-many relationship (purchaseOrders -> purchase_orders)
  if (
    row.purchase_orders &&
    Array.isArray(row.purchase_orders) &&
    row.purchase_orders.length > 0
  ) {
    return row.purchase_orders;
  }

  // Check for camelCase version (in case serialization differs)
  if (
    row.purchaseOrders &&
    Array.isArray(row.purchaseOrders) &&
    row.purchaseOrders.length > 0
  ) {
    return row.purchaseOrders;
  }

  // Fallback to single purchase_order if purchase_orders is not available
  if (row.purchase_order) {
    return [row.purchase_order];
  }

  // Check camelCase version of single relationship
  if (row.purchaseOrder) {
    return [row.purchaseOrder];
  }

  return [];
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

  // Fallback: check if supplier data exists directly on the Payment Voucher row
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

function extractSupplierName(supplier: any): string | null {
  if (supplier == null) return null;

  // If it's a string, try to parse JSON, otherwise use the string value
  if (typeof supplier === "string") {
    const trimmed = supplier.trim();
    if (!trimmed || trimmed.toLowerCase() === "null" || trimmed === "[object Object]") {
      return null;
    }
    try {
      const parsed = JSON.parse(trimmed);
      return (
        parsed?.nama_supplier ||
        parsed?.nama ||
        parsed?.name ||
        null
      ) ?? null;
    } catch {
      // Not JSON, return as is
      return trimmed;
    }
  }

  // If it's an object, try known keys
  if (typeof supplier === "object") {
    return supplier?.nama_supplier || supplier?.nama || supplier?.name || null;
  }

  // Fallback to primitive conversion
  const val = String(supplier).trim();
  return val && val.toLowerCase() !== "null" ? val : null;
}

function formatSupplier(row: any): string | null {
  // If payment method is Kredit/Kartu Kredit, do not show supplier (display '-')
  const metodeRaw =
    (typeof row?.metode_bayar === 'string' ? row.metode_bayar : null) ||
    (typeof row?.metode_pembayaran === 'string' ? row.metode_pembayaran : null) ||
    (typeof row?.purchase_order?.metode_pembayaran === 'string' ? row.purchase_order.metode_pembayaran : null) ||
    (typeof row?.purchaseOrder?.metode_pembayaran === 'string' ? row.purchaseOrder.metode_pembayaran : null) ||
    (typeof row?.memo_pembayaran?.metode_pembayaran === 'string' ? row.memo_pembayaran.metode_pembayaran : null) ||
    (typeof row?.memoPembayaran?.metode_pembayaran === 'string' ? row.memoPembayaran.metode_pembayaran : null);
  if (typeof metodeRaw === 'string') {
    const metode = metodeRaw.trim().toLowerCase();
    if (metode === 'kredit' || metode === 'kartu kredit' || metode.includes('kredit')) {
      return null;
    }
  }
  // 1) Direct supplier on the row
  const direct = extractSupplierName(row?.supplier) ?? row?.supplier_name ?? null;
  if (direct) return direct;

  // 2) From related Purchase Orders
  const purchaseOrders = getAllPurchaseOrders(row);
  for (const po of purchaseOrders) {
    const nameFromPO =
      extractSupplierName(po?.supplier) ||
      extractSupplierName(po?.supplier_name) ||
      po?.supplier_name ||
      null;
    if (nameFromPO) return nameFromPO;
  }

  // 3) Fallback to legacy extractor to keep compatibility
  const legacy = getSupplierFromPurchaseOrders(row);
  const legacyName = extractSupplierName(legacy);
  if (legacyName) return legacyName;

  return null;
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

/* Button hover effects */
button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
