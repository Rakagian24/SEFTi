<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6md:px-6 md:pt-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Failed PO Message Panel -->
      <div v-if="showFailedMessage" class="mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 shadow-lg">
          <!-- Progress Bar for Auto-hide -->
          <div
            class="absolute top-0 left-0 h-1 bg-amber-300 rounded-t-lg transition-all duration-1000"
            :style="{ width: progressWidth + '%' }"
          ></div>

          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-semibold text-amber-800">
                  {{ failedMessageTitle }}
                </h3>
                <div class="flex items-center gap-2">
                  <span
                    class="text-xs text-amber-600 bg-amber-100 px-2 py-1 rounded-full"
                  >
                    Auto-hide dalam {{ countdownSeconds }}s
                  </span>
                  <button
                    @click="closeFailedMessage"
                    class="inline-flex text-amber-400 hover:text-amber-500 focus:outline-none focus:text-amber-500 transition-colors"
                  >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </button>
                </div>
              </div>

              <div class="text-sm text-amber-700 mb-4">
                <p>{{ failedMessageSummary }}</p>
              </div>

              <!-- Failed PO Details -->
              <div class="space-y-3">
                <div
                  v-for="po in failedPOs"
                  :key="po.id"
                  class="bg-white rounded-lg p-4 border border-amber-200 shadow-sm hover:shadow-md transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                        <div class="font-semibold text-amber-900 text-base">
                          {{ po.no_po || `PO #${po.id}` }}
                        </div>
                        <span
                          class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded-full font-medium"
                        >
                          Belum Lengkap
                        </span>
                      </div>
                      <div class="text-amber-800 text-sm space-y-1.5 ml-4">
                        <div
                          v-for="(error, index) in po.errors"
                          :key="index"
                          class="flex items-start"
                        >
                          <span
                            class="w-1.5 h-1.5 bg-amber-400 rounded-full mr-2 mt-1.5 flex-shrink-0"
                          ></span>
                          <span>{{ error }}</span>
                        </div>
                      </div>
                    </div>
                    <button
                      @click="editFailedPO(po.id)"
                      class="ml-4 text-sm bg-amber-100 text-amber-800 px-3 py-2 rounded-md hover:bg-amber-200 transition-colors font-medium flex items-center gap-1"
                    >
                      <svg
                        class="w-4 h-4"
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
                      Edit
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-6 hidden items-center justify-between md:flex">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Purchase Order</h1>
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <CreditCard class="mr-1 h-4 w-4" />
            Manage Purchase Order data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <button
              @click="openConfirmSend"
              :disabled="!canSendSelected"
              class="flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <Send class="h-4 w-4" />
              Kirim ({{ selected.length }})
            </button>
          </div>

          <button
            @click="goToAdd"
            class="flex items-center gap-2 rounded-md bg-[#101010] px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Add New
          </button>
        </div>
      </div>

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">Purchase Order</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <CreditCard class="mr-1 h-3 w-3" />
          Manage Purchase Order data
        </div>
      </div>

      <!-- Mobile actions: Kirim + Add New -->
      <div class="mb-4 flex items-center justify-between gap-2 md:hidden">
        <div class="text-xs text-gray-600">
          <span v-if="selected.length > 0" class="font-semibold text-blue-600">
            {{ selected.length }}
          </span>
          <span v-else class="text-gray-400">0</span>
          dokumen dipilih
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openConfirmSend"
            :disabled="!canSendSelected"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              canSendSelected
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span>Kirim</span>
          </button>

          <button
            type="button"
            @click="goToAdd"
            class="inline-flex items-center gap-1 rounded-lg bg-[#101010] px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white hover:text-[#101010]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span>Add New</span>
          </button>
        </div>
      </div>

      <!-- Global confirm dialog for both desktop and mobile -->
      <ConfirmDialog
        :show="showConfirmSend"
        :message="`Apakah Anda yakin ingin mengirim ${selected.length} Purchase Order?`"
        @confirm="confirmSend"
        @cancel="cancelSend"
      />

      <!-- Desktop / Tablet: Filters + Table -->
      <div class="hidden md:block">
        <PurchaseOrderFilter
          :filters="filters"
          :departments="departments"
          :perihals="perihals"
          :columns="columns"
          :entries-per-page="filters.per_page || 10"
          @filter="applyFilters"
          @reset="resetFilters"
          @update:columns="updateColumns"
          @update:entries-per-page="updateEntriesPerPage"
        />

        <PurchaseOrderTable
          :data="purchaseOrders?.data || []"
          :pagination="purchaseOrders"
          :selected="selected"
          :columns="columns"
          @select="onSelect"
          @action="handleAction"
          @paginate="handlePagination"
          @add="goToAdd"
        />
      </div>

      <!-- Mobile: Card list -->
      <div class="mt-4 md:hidden">
        <!-- Simple search bound to current filters -->
        <div class="mb-4">
          <input
            v-model="currentFilters.search"
            type="text"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="Cari No. PO / Perihal / Supplier / Bisnis Partner"
            @keyup.enter="loadPurchaseOrders({ page: 1 })"
            @blur="loadPurchaseOrders({ page: 1 })"
          />
        </div>

        <div
          v-if="(purchaseOrders?.data || []).length === 0"
          class="py-8 text-center text-sm text-gray-500"
        >
          Belum ada Purchase Order yang terdaftar.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="po in purchaseOrders?.data || []"
            :key="po.id"
            class="w-full rounded-xl bg-white p-3 text-left shadow-sm active:bg-slate-50"
          >
            <div class="mb-1 flex items-start justify-between">
              <div class="flex items-center gap-2">
                <input
                  v-if="canSendRow(po)"
                  type="checkbox"
                  :value="po.id"
                  v-model="selected"
                  class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500 self-center"
                  @click.stop
                />
                <div>
                  <div class="text-xs font-semibold text-gray-500">No. PO</div>
                  <div class="text-xs font-semibold text-gray-900">
                    {{ po.no_po || '-' }}
                  </div>
                </div>
              </div>

              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="getStatusBadgeClassMobile(po.status)">
                {{ po.status || '-' }}
              </span>
            </div>

            <div class="mt-1 text-xs text-gray-500 truncate">
              {{ po.perihal?.nama || '-' }}
            </div>

            <div class="mt-2 flex items-end justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="text-[11px] text-gray-500">
                  {{
                    po.bisnis_partner?.nama_bp
                      ? 'Bisnis Partner'
                      : po.supplier?.nama_supplier
                      ? 'Supplier'
                      : 'Supplier / Bisnis Partner'
                  }}
                </div>
                <div class="truncate text-xs font-medium text-gray-900">
                  {{ po.bisnis_partner?.nama_bp || po.supplier?.nama_supplier || '-' }}
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-gray-500">Grand Total</div>
                <div class="text-sm font-semibold text-emerald-700">
                  {{ formatCurrency(po.grand_total || 0) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-400">
                  {{ po.tanggal ? formatDate(po.tanggal) : '-' }}
                </div>
              </div>
            </div>

            <!-- Mobile card actions menu -->
            <div class="mt-2 flex justify-end">
              <div class="relative inline-block text-left">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                  @click.stop="toggleMobileMenu(po.id)"
                >
                  <span class="sr-only">Buka menu</span>
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"
                    />
                  </svg>
                </button>

                <div
                  v-if="mobileMenuPoId === po.id"
                  class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-lg bg-white py-1 text-xs shadow-lg ring-1 ring-black/5"
                >
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canShowDetailMobile(po)"
                    @click.stop="handleMobileAction('detail', po)"
                  >
                    Detail
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('download', po)"
                  >
                    Download
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('log', po)"
                  >
                    Log
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canEditRowMobile(po)"
                    @click.stop="handleMobileAction('edit', po)"
                  >
                    Edit
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div
          v-if="purchaseOrders && (purchaseOrders.data || []).length > 0"
          class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
        >
          <nav
            class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
            aria-label="Pagination"
          >
            <button
              type="button"
              @click="handlePagination(purchaseOrders.prev_page_url)"
              :disabled="!purchaseOrders.prev_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                purchaseOrders.prev_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Prev
            </button>

            <div class="px-3 py-1 text-[11px] text-gray-500">
              Halaman
              <span class="font-semibold text-gray-800">{{ purchaseOrders.current_page || 1 }}</span>
            </div>

            <button
              type="button"
              @click="handlePagination(purchaseOrders.next_page_url)"
              :disabled="!purchaseOrders.next_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                purchaseOrders.next_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Next
            </button>
          </nav>
        </div>
      </div>

      <StatusLegend entity="Purchase Order" />

      <!-- Confirm Delete Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="
          confirmRow ? `Apakah Anda yakin ingin menghapus Purchase Order ini?` : ''
        "
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />

      <!-- Close Reason Dialog -->
      <CloseReasonDialog
        :is-open="showCloseReasonDialog"
        @update:open="(val: boolean) => (showCloseReasonDialog = val)"
        @cancel="cancelClose"
        @confirm="confirmClose"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import PurchaseOrderTable from "../../components/purchase-orders/PurchaseOrderTable.vue";
import PurchaseOrderFilter from "../../components/purchase-orders/PurchaseOrderFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { CreditCard, Send } from "lucide-vue-next";
import CloseReasonDialog from "@/components/approval/CloseReasonDialog.vue";
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Purchase Order" }];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const props = defineProps<{
  purchaseOrders: any;
  filters: Record<string, any>;
  departments: any[];
  perihals: any[];
  columns: Column[];
}>();

// Watch for props changes (debug removed)

// Get page props for flash messages and session data
const page = usePage();

// Watch for flash messages and session data changes
watch(
  () => page.props,
  (newProps: any) => {
    // Handle success flash message
    if (newProps.flash?.success) {
      addSuccess(newProps.flash.success);
    }

    // Handle failed POs from session data
    if (
      newProps.failed_pos &&
      Array.isArray(newProps.failed_pos) &&
      newProps.failed_pos.length > 0
    ) {
      failedPOs.value = newProps.failed_pos;
      failedMessageTitle.value = "Beberapa Purchase Order Gagal Dikirim";

      const updatedCount = newProps.updated_pos?.length || 0;
      const failedCount = newProps.failed_pos.length;

      failedMessageSummary.value = `${updatedCount} PO berhasil dikirim, ${failedCount} PO gagal karena data belum lengkap. Silakan lengkapi data yang diperlukan terlebih dahulu sebelum mengirim ulang.`;
      showFailedMessageWithTimer();
    }
  },
  { immediate: false, deep: false }
);

// State untuk message panel failed PO
const showFailedMessage = ref(false);
const failedPOs = ref<any[]>([]);
const failedMessageTitle = ref("");
const failedMessageSummary = ref("");
const selected = ref<number[]>([]);
// const canSend = computed(() => selected.value.length > 0);

// Get current user info
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props.auth as any)?.user?.id;
  return id ?? null;
});

const isAdmin = computed<boolean>(() => {
  const userRole = (page.props.auth as any)?.user?.role?.name;
  return userRole === "Admin";
});

// Check if user can send selected items
const canSendSelected = computed(() => {
  if (selected.value.length === 0) return false;

  const rows = (purchaseOrders.value?.data || []) as any[];
  const selectedRows = rows.filter((r) => selected.value.includes(r.id));

  // Check if user can send all selected items
  return selectedRows.every((row) => {
    if (row.status === "Draft" || row.status === "Rejected") {
      const isCreator = isCreatorRow(row);
      return isCreator || isAdmin.value;
    }
    return false;
  });
});

// Helper to decide if a single row can be sent (used by mobile checkbox)
function canSendRow(row: any) {
  if (!row) return false;
  if (!(row.status === "Draft" || row.status === "Rejected")) return false;
  return isCreatorRow(row) || isAdmin.value;
}

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);
const showConfirmCloseDialog = ref(false);
const closeRow = ref<any>(null);
const showCloseReasonDialog = ref(false);

const departments = ref(props.departments || []);
const perihals = ref(props.perihals || []);

// Local state for purchase orders data
const purchaseOrders = ref(
  props.purchaseOrders || { data: [], total: 0, current_page: 1, last_page: 1 }
);
const currentFilters = ref(props.filters || {});

watch(
  () => props.purchaseOrders,
  (val) => {
    purchaseOrders.value =
      val || { data: [], total: 0, current_page: 1, last_page: 1 };
  },
  { deep: true }
);

// Default columns configuration
const defaultColumns: Column[] = [
  { key: "no_po", label: "No. PO", checked: true, sortable: true },
  { key: "no_invoice", label: "No. Invoice", checked: false, sortable: true },
  { key: "tipe_po", label: "Tipe PO", checked: false, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "department", label: "Departemen", checked: true, sortable: false },
  { key: "perihal", label: "Perihal", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: false, sortable: false },
  { key: "bisnis_partner", label: "Bisnis Partner", checked: false, sortable: false },
  { key: "keterangan", label: "Keterangan", checked: false, sortable: false },
  {
    key: "metode_pembayaran",
    label: "Metode Pembayaran",
    checked: false,
    sortable: false,
  },
  { key: "total", label: "Total", checked: false, sortable: true },
  { key: "diskon", label: "Diskon", checked: false, sortable: true },
  { key: "ppn", label: "PPN", checked: false, sortable: true },
  { key: "pph", label: "PPH", checked: false, sortable: true },
  { key: "grand_total", label: "Grand Total", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
];

const columns = ref<Column[]>(props.columns || defaultColumns);

// Mobile card actions menu state
const mobileMenuPoId = ref<number | null>(null);

function toggleMobileMenu(poId: number) {
  mobileMenuPoId.value = mobileMenuPoId.value === poId ? null : poId;
}

function handleMobileAction(
  action: "detail" | "download" | "log" | "edit",
  row: any
) {
  mobileMenuPoId.value = null;
  if (!row?.id) return;

  if (action === "detail") {
    router.visit(`/purchase-orders/${row.id}`);
  } else if (action === "download") {
    window.open(`/purchase-orders/${row.id}/download`, "_blank");
  } else if (action === "log") {
    router.visit(`/purchase-orders/${row.id}/log`);
  } else if (action === "edit") {
    router.visit(`/purchase-orders/${row.id}/edit`);
  }
}

// Mobile helpers to mirror desktop action visibility rules
function canEditRowMobile(row: any) {
  if (!row) return false;
  if (row.status === "Draft") {
    return isCreatorRow(row);
  }
  if (row.status === "Rejected") {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

function canShowDetailMobile(row: any) {
  if (!row) return false;
  const isCreator = isCreatorRow(row);
  if (row.status === "Draft") {
    return !isCreator;
  }
  if (row.status === "Rejected") {
    return !isCreator;
  }
  // For other statuses, detail is always visible like desktop
  return true;
}

function getStatusBadgeClassMobile(status: string) {
  return getSharedStatusBadgeClass(status || "Draft");
}

function formatDate(value?: string) {
  if (!value) return "-";
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function formatCurrency(amount: number | null | undefined) {
  if (amount === null || amount === undefined) return "-";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

// Function to load purchase orders data
async function loadPurchaseOrders(params: Record<string, any> = {}) {
  try {
    const query = new URLSearchParams();

    // Add current filters
    Object.entries(currentFilters.value).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.set(key, String(value));
      }
    });

    // Add new params
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.set(key, String(value));
      }
    });

    const response = await axios.get(`/purchase-orders?${query.toString()}`, {
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
    });

    // Check if response is HTML (Inertia response)
    if (typeof response.data === "string" && response.data.includes("<!DOCTYPE html>")) {
      console.error(
        "Received HTML response instead of JSON. This might be an authentication issue."
      );
      return;
    }

    purchaseOrders.value = response.data;
  } catch (err: any) {
    console.error("Error loading purchase orders:", err);
    if (err && err.response) {
      console.error("Response status:", err.response.status);
      console.error("Response data:", err.response.data);
    }
  }
}

onMounted(() => {
  // Initialize columns from props or use defaults
  if (props.columns && props.columns.length > 0) {
    columns.value = props.columns;
  } else {
    columns.value = defaultColumns;
  }

  // Check for flash messages and session data on mount
  const pageProps = page.props as any;

  if (pageProps.flash?.success) {
    addSuccess(pageProps.flash.success);
  }

  if (
    pageProps.failed_pos &&
    Array.isArray(pageProps.failed_pos) &&
    pageProps.failed_pos.length > 0
  ) {
    failedPOs.value = pageProps.failed_pos;
    failedMessageTitle.value = "Beberapa Purchase Order Gagal Dikirim";

    const updatedCount = pageProps.updated_pos?.length || 0;
    const failedCount = pageProps.failed_pos.length;

    failedMessageSummary.value = `${updatedCount} PO berhasil dikirim, ${failedCount} PO gagal karena data belum lengkap. Silakan lengkapi data yang diperlukan terlebih dahulu sebelum mengirim ulang.`;
    showFailedMessageWithTimer();
  }
});

function applyFilters(payload: Record<string, any>) {
  // Update current filters
  currentFilters.value = {
    ...currentFilters.value,
    ...payload,
    per_page: payload.entriesPerPage || currentFilters.value.per_page || 10,
  };

  // Load data with new filters
  loadPurchaseOrders();
}

function resetFilters() {
  // Reset columns to defaults when resetting filters
  columns.value = [...defaultColumns];
  currentFilters.value = { per_page: 10, columns: JSON.stringify(columns.value) };
  loadPurchaseOrders();
}

function updateColumns(newColumns: Column[]) {
  columns.value = newColumns;
  currentFilters.value.columns = JSON.stringify(newColumns);
  loadPurchaseOrders();
}

function updateEntriesPerPage(newPerPage: number) {
  currentFilters.value.per_page = newPerPage;
  loadPurchaseOrders();
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split("?")[1]);
  const page = urlParams.get("page");
  if (page) {
    currentFilters.value.page = page;
    loadPurchaseOrders();
  }
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleAction(payload: { action: string; row: any }) {
  const { action, row } = payload;
  if (action === "edit") router.visit(`/purchase-orders/${row.id}/edit`);
  if (action === "delete") {
    confirmRow.value = row;
    showConfirmDialog.value = true;
  }
  if (action === "close") {
    closeRow.value = row;
    showCloseReasonDialog.value = true;
  }
  if (action === "detail") router.visit(`/purchase-orders/${row.id}`);
  if (action === "log") router.visit(`/purchase-orders/${row.id}/log`);
  if (action === "download") window.open(`/purchase-orders/${row.id}/download`, "_blank");
}

// Tambahan state untuk konfirmasi kirim
const showConfirmSend = ref(false);

// Fungsi open confirm send
function openConfirmSend() {
  if (!canSendSelected.value) return;
  showConfirmSend.value = true;
}

// Fungsi konfirmasi kirim
function confirmSend() {
  router.post(
    "/purchase-orders/send",
    { ids: selected.value },
    {
      onSuccess: () => {
        selected.value = [];
        loadPurchaseOrders();
      },
      onError: () => addError("Terjadi kesalahan saat mengirim Purchase Order"),
      preserveScroll: true,
    }
  );
  showConfirmSend.value = false;
}

// Fungsi batal kirim
function cancelSend() {
  showConfirmSend.value = false;
}

function confirmDelete() {
  if (confirmRow.value) {
    router.delete(`/purchase-orders/${confirmRow.value.id}`);
  }
  cancelDelete();
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function confirmClose(reason: string) {
  if (!closeRow.value) return;

  router.post(
    `/purchase-orders/${closeRow.value.id}/close`,
    { reason },
    {
      onSuccess: () => {
        loadPurchaseOrders();
      },
      onError: () => addError("Terjadi kesalahan saat menutup Purchase Order"),
      preserveScroll: true,
    }
  );
  cancelClose();
}

function cancelClose() {
  showConfirmCloseDialog.value = false;
  showCloseReasonDialog.value = false;
  closeRow.value = null;
}

function goToAdd() {
  router.visit("/purchase-orders/create");
}

// Function untuk edit PO yang gagal
function editFailedPO(poId: number) {
  router.visit(`/purchase-orders/${poId}/edit`);
}

// Function untuk menutup message panel failed PO
function closeFailedMessage() {
  showFailedMessage.value = false;
  failedPOs.value = [];
  failedMessageTitle.value = "";
  failedMessageSummary.value = "";
}

// Auto-hide message panel after 10 seconds
function showFailedMessageWithTimer() {
  showFailedMessage.value = true;
  progressWidth.value = 0;
  countdownSeconds.value = 10;

  const totalSeconds = 10;
  const interval = setInterval(() => {
    countdownSeconds.value--;
    progressWidth.value = ((totalSeconds - countdownSeconds.value) / totalSeconds) * 100;

    if (countdownSeconds.value <= 0) {
      clearInterval(interval);
      closeFailedMessage();
    }
  }, 1000);
}

// State untuk countdown dan progress bar
const progressWidth = ref(0);
const countdownSeconds = ref(10);
</script>
