<template>
  <!-- Empty State -->
  <EmptyState
    v-if="!rows || rows.length === 0"
    title="No Payment Vouchers found"
    description="There are no payment vouchers to display. Start by creating your first payment voucher."
    icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
    action-text="Create Payment Voucher"
    :show-action="false"
  />

  <!-- Table with data -->
  <div v-else class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              v-if="hasAnySelectable"
              class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll($event)"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
            </th>
            <!-- Dynamic headers based on columns prop -->
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
              :class="getColumnClass(col.key)"
            >
              {{ col.label }}
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]"
            >
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in rows" :key="row.id" class="alternating-row">
            <td
              v-if="hasAnySelectable"
              class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]"
            >
              <input
                v-if="(row.status === 'Draft' || row.status === 'Rejected') && canSelectRow(row)"
                type="checkbox"
                :checked="selectedIds.has(row.id)"
                @change="toggleRow(row.id, $event)"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
            </td>
            <!-- Dynamic data cells based on columns prop -->
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-6 py-4 whitespace-nowrap text-sm"
              :class="getCellClass(col.key)"
            >
              <template v-if="col.key === 'no_pv'">
                <span class="font-medium text-gray-900">{{ row.no_pv || "-" }}</span>
              </template>
              <template v-else-if="col.key === 'no_po'">
                <span class="font-medium text-gray-900">{{ row.no_po || "-" }}</span>
              </template>
              <template v-else-if="col.key === 'no_bk'">
                {{ row.no_bk || "-" }}
              </template>
              <template v-else-if="col.key === 'tanggal'">
                {{ row.tanggal ? formatDate(row.tanggal) : "-" }}
              </template>
              <template v-else-if="col.key === 'status'">
                <span
                  :class="getStatusBadgeClass(row.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ row.status }}
                </span>
              </template>
              <template v-else-if="col.key === 'supplier'">
                {{ row.supplier_name || "-" }}
              </template>
              <template v-else-if="col.key === 'department'">
                {{ row.department_name || "-" }}
              </template>
              <template v-else>
                {{ getRowValue(row, col.key) ?? "-" }}
              </template>
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell"
            >
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit Button -->
                <button
                  v-if="canEditRow(row)"
                  @click="handleEdit(row)"
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
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>

                <!-- Cancel Button -->
                <button
                  v-if="canDeleteRow(row)"
                  @click="handleCancel(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Cancel"
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

                <!-- Detail Button -->
                <button
                  v-if="
                    (row.status === 'Draft' && !isCreatorRow(row)) ||
                    (row.status !== 'Draft' && (row.status !== 'Rejected' || (row.status === 'Rejected' && !isCreatorRow(row))))
                  "
                  @click="handleDetail(row)"
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
                  v-if="row.status !== 'Draft' && row.status !== 'Rejected'"
                  @click="handleDownload(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-purple-50 hover:bg-purple-100 transition-colors duration-200"
                  title="Download"
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
                  @click="handleLog(row)"
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
          <tr v-if="!rows || !rows.length">
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
      v-if="pagination"
      class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
    >
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="goToPage(pagination?.prev_page_url)"
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
          @click="goToPage(pagination?.next_page_url)"
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
      @confirm="onConfirmCancel"
      @cancel="onCancelCancel"
    />
  </div>
</template>

<script setup lang="ts">
import EmptyState from "@/components/ui/EmptyState.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useAlertDialog } from "@/composables/useAlertDialog";

type PvRow = {
  id: number | string;
  no_pv?: string | null;
  no_po?: string | null;
  no_bk?: string | null;
  tanggal?: string | null;
  status: "Draft" | "In Progress" | "Rejected" | "Approved" | "Canceled";
  supplier_name?: string | null;
  department_name?: string | null;
};

const props = defineProps<{
  rows: PvRow[];
  selectedIds: Set<PvRow["id"]>;
  pagination?: any;
  visibleColumns?: Array<{ key: string; label: string; checked: boolean }>;
}>();

const emit = defineEmits(["toggleAll", "toggleRow", "paginate", "cancel"]);

const columns = computed(() =>
  (
    props.visibleColumns || [
      { key: "no_pv", label: "No. PV", checked: true },
      { key: "no_po", label: "No. PO", checked: true },
      { key: "no_bk", label: "No. BK", checked: true },
      { key: "tanggal", label: "Tanggal", checked: true },
      { key: "status", label: "Status", checked: true },
      { key: "supplier", label: "Supplier", checked: true },
      { key: "department", label: "Departemen", checked: true },
    ]
  ).filter((c) => c.checked)
);
// Page/user and permission helpers
const page = usePage();
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props as any)?.auth?.user?.id;
  return id ?? null;
});
const isAdmin = computed<boolean>(() => {
  const userRole = (page.props as any)?.auth?.user?.role?.name;
  return userRole === "Admin";
});

function isCreatorRow(row: PvRow | any) {
  const creatorId = (row as any)?.creator?.id ?? (row as any)?.created_by_id ?? (row as any)?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function canEditRow(row: PvRow | any) {
  if (row.status === "Draft") return isCreatorRow(row);
  if (row.status === "Rejected") return isCreatorRow(row) || isAdmin.value;
  return false;
}

function canDeleteRow(row: PvRow | any) {
  if (row.status === "Draft") return isCreatorRow(row) || isAdmin.value;
  return false;
}

function canSelectRow(row: PvRow | any) {
  if (row.status === "Draft") return isCreatorRow(row) || isAdmin.value;
  if (row.status === "Rejected") return isCreatorRow(row) || isAdmin.value;
  return false;
}

const allSelectableIds = computed(() =>
  props.rows
    .filter((r) => (r.status === "Draft" || r.status === "Rejected") && canSelectRow(r))
    .map((r) => r.id)
);
const isAllSelected = computed(
  () =>
    allSelectableIds.value.length > 0 &&
    allSelectableIds.value.every((id) => props.selectedIds.has(id))
);
const hasAnySelectable = computed(() => allSelectableIds.value.length > 0);
const { showError } = useAlertDialog();

function toggleSelectAll(event: Event) {
  const target = event.target as HTMLInputElement | null;
  const isChecked = target?.checked ?? false;
  emit("toggleAll", isChecked);
}

function toggleRow(id: PvRow["id"], event: Event) {
  const target = event.target as HTMLInputElement | null;
  const isChecked = target?.checked ?? false;
  emit("toggleRow", { id, val: isChecked });
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString("id-ID");
}

function getColumnClass(key: string) {
  // Add specific styling for certain columns if needed
  void key;
  return "";
}

function getCellClass(key: string) {
  // Add specific styling for certain cells
  if (key === "no_pv" || key === "no_po") {
    return "font-medium text-gray-900";
  }
  return "text-[#101010]";
}

function getRowValue(row: PvRow, key: string) {
  const value = (row as Record<string, unknown>)[key];
  return (value as string | number | null | undefined) ?? null;
}

function getTotalColumns() {
  let total = columns.value.length;
  if (hasAnySelectable.value) total += 1; // Checkbox column
  total += 1; // Action column
  return total;
}

function getStatusBadgeClass(status: string) {
  switch (status) {
    case "Draft":
      return "bg-gray-100 text-gray-700";
    case "In Progress":
      return "bg-blue-100 text-blue-800";
    case "Approved":
      return "bg-green-100 text-green-800";
    case "Rejected":
      return "bg-red-100 text-red-800";
    case "Canceled":
      return "bg-gray-100 text-gray-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}

function handleEdit(row: PvRow) {
  window.location.href = `/payment-voucher/${row.id}/edit`;
}

// Confirm dialog state for cancel
const showConfirm = ref(false);
const confirmTargetId = ref<PvRow["id"] | null>(null);
const confirmMessage = ref<string>("Apakah Anda yakin ingin membatalkan payment voucher ini?");

function handleCancel(row: PvRow) {
  confirmTargetId.value = row.id;
  confirmMessage.value = `Apakah Anda yakin ingin membatalkan payment voucher ${row.no_pv || "ini"}?`;
  showConfirm.value = true;
}

function onConfirmCancel() {
  if (confirmTargetId.value != null) {
    emit("cancel", confirmTargetId.value);
  }
  confirmTargetId.value = null;
  showConfirm.value = false;
}

function onCancelCancel() {
  confirmTargetId.value = null;
  showConfirm.value = false;
}

function handleDetail(row: PvRow) {
  window.location.href = `/payment-voucher/${row.id}`;
}

function handleDownload(row: PvRow) {
  try {
    // Create a temporary link element to trigger download
    const link = document.createElement("a");
    link.href = `/payment-voucher/${row.id}/download`;
    link.target = "_blank";
    link.download = `PaymentVoucher_${row.no_pv || "Draft"}.pdf`;

    // Append to body, click, and remove
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error("Download error:", error);
    showError(
      "Failed to download PDF. Please try again. If the problem persists, contact support.",
      "Download Error"
    );
  }
}

function handleLog(row: PvRow) {
  window.location.href = `/payment-voucher/${row.id}/log`;
}

function goToPage(url: string) {
  if (url) {
    emit("paginate", url);
    window.dispatchEvent(new CustomEvent("pagination-changed"));
    window.dispatchEvent(new CustomEvent("table-changed"));
  }
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
