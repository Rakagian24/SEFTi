<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <!-- Desktop / Tablet Layout -->
    <div class="px-4 pt-4 pb-6 hidden md:block">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Payment Voucher Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <TicketPercent class="w-4 h-4 mr-1" />
            Dokumen Payment Voucher yang menunggu persetujuan
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
          <!-- Selected Count -->
          <div class="text-sm text-gray-600">
            <span v-if="selectedPaymentVouchers.length > 0" class="font-medium text-blue-600">
              {{ selectedPaymentVouchers.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>

          <!-- Primary Process Button (dynamic: Verifikasi/Validasi/Setujui) -->
          <button
            @click="handleBulkApprove"
            :disabled="selectedPaymentVouchers.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedPaymentVouchers.length > 0
                ? getApprovalButtonClassForTemplate(bulkActionType)
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            {{ bulkActionLabel }}
          </button>

          <!-- Reject Button -->
          <button
            @click="handleBulkReject"
            :disabled="selectedPaymentVouchers.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedPaymentVouchers.length > 0
                ? 'bg-white text-red-600 border border-red-600 hover:bg-red-50'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Tolak
          </button>
        </div>
      </div>

      <!-- Filter Component -->
      <PaymentVoucherApprovalFilter
        :filters="filters"
        :departments="departments"
        :columns="columns"
        :entries-per-page="filters.per_page || 10"
        @filter="handleFilter"
        @reset="resetFilters"
        @update:entries-per-page="updateEntriesPerPage"
        @update:columns="updateColumns"
      />

      <!-- Table Component -->
      <PaymentVoucherApprovalTable
        :data="paymentVouchers"
        :loading="loading"
        :selected="selectedPaymentVouchers"
        :pagination="pagination"
        :columns="columns"
        :selectable-statuses="selectableStatuses"
        :is-row-selectable="isRowSelectableForRole"
        @select="handleSelect"
        @action="handleAction"
        @paginate="handlePaginate"
      />

      <StatusLegend entity="Payment Voucher" />
    </div>

    <!-- Mobile Layout -->
    <div class="px-4 pt-6 pb-6 md:hidden">
      <!-- Header -->
      <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-900">Payment Voucher Approval</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <TicketPercent class="mr-1 h-3 w-3" />
          Dokumen Payment Voucher yang menunggu persetujuan
        </div>
      </div>

      <!-- Mobile bulk actions -->
      <div class="mb-4 flex items-center justify-between gap-2">
        <div class="text-xs text-gray-600">
          <span
            v-if="selectedPaymentVouchers.length > 0"
            class="font-semibold text-blue-600"
          >
            {{ selectedPaymentVouchers.length }}
          </span>
          <span v-else class="text-gray-400">0</span>
          dokumen dipilih
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="handleBulkApprove"
            :disabled="selectedPaymentVouchers.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selectedPaymentVouchers.length > 0
                ? getApprovalButtonClassForTemplate(bulkActionType)
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
            <span>{{ bulkActionLabel }}</span>
          </button>

          <button
            type="button"
            @click="handleBulkReject"
            :disabled="selectedPaymentVouchers.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selectedPaymentVouchers.length > 0
                ? 'bg-white text-red-600 border border-red-600 hover:bg-red-50'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            <span>Tolak</span>
          </button>
        </div>
      </div>

      <!-- Search bar -->
      <div class="mb-4 flex items-center gap-2">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Cari No. PV / Supplier / Bisnis Partner / Perihal"
          class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <button
          type="button"
          class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm active:bg-blue-700"
          @click="fetchPaymentVouchers"
        >
          Cari
        </button>
      </div>

      <!-- List PV -->
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="animate-pulse rounded-xl bg-white p-3 shadow-sm">
          <div class="mb-2 flex justify-between">
            <div class="h-4 w-28 rounded bg-slate-200" />
            <div class="h-4 w-16 rounded bg-slate-200" />
          </div>
          <div class="mb-2 h-3 w-40 rounded bg-slate-200" />
          <div class="flex items-end justify-between">
            <div class="h-4 w-24 rounded bg-slate-200" />
            <div class="h-3 w-20 rounded bg-slate-200" />
          </div>
        </div>
      </div>

      <div v-else>
        <div
          v-if="paymentVouchers.length === 0"
          class="py-8 text-center text-sm text-gray-500"
        >
          Belum ada Payment Voucher yang menunggu approval.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="pv in paymentVouchers"
            :key="pv.id"
            class="w-full rounded-xl bg-white p-3 text-left shadow-sm active:bg-slate-50"
          >
            <div class="mb-1 flex items-start justify-between">
              <div class="flex items-start gap-2">
                <input
                  v-if="isRowSelectableForRole(pv) && selectableStatuses.includes(pv.status)"
                  type="checkbox"
                  :value="pv.id"
                  v-model="selectedPaymentVouchers"
                  class="mt-4 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500"
                  @click.stop
                />
                <div>
                  <div class="text-xs font-semibold text-gray-500">No. PV</div>
                  <div class="text-xs font-semibold text-gray-900">
                    {{ pv.no_pv || '-' }}
                  </div>
                </div>
              </div>
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="pv.status === 'Approved'
                  ? 'bg-green-100 text-green-700'
                  : pv.status === 'Rejected'
                  ? 'bg-red-100 text-red-700'
                  : 'bg-blue-100 text-blue-700'"
              >
                {{ pv.status || '-' }}
              </span>
            </div>

            <div class="mt-1 text-xs text-gray-500 truncate">
              {{ pv.perihal?.nama || '-' }}
            </div>

            <div class="mt-2 flex items-end justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="text-[11px] text-gray-500">
                  {{
                    pv.bisnis_partner?.nama_bp
                      ? 'Bisnis Partner'
                      : pv.supplier?.nama_supplier
                      ? 'Supplier'
                      : 'Supplier / Bisnis Partner'
                  }}
                </div>
                <div class="truncate text-xs font-medium text-gray-900">
                  {{ pv.bisnis_partner?.nama_bp || pv.supplier?.nama_supplier || '-' }}
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-gray-500">Grand Total</div>
                <div class="text-sm font-semibold text-emerald-700">
                  {{ formatCurrency(getDisplayGrandTotal(pv)) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-400">
                  {{ pv.tanggal ? new Date(pv.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                </div>
              </div>
            </div>

            <!-- Mobile card actions menu -->
            <div class="mt-2 flex justify-end">
              <div class="relative inline-block text-left">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                  @click.stop="toggleMobileMenu(pv.id)"
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
                  v-if="mobileMenuPvId === pv.id"
                  class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-lg bg-white py-1 text-xs shadow-lg ring-1 ring-black/5"
                >
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('detail', pv)"
                  >
                    Detail
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('download', pv)"
                  >
                    Download
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('log', pv)"
                  >
                    Log
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div
          v-if="pagination && paymentVouchers.length > 0"
          class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
        >
          <nav
            class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
            aria-label="Pagination"
          >
            <button
              type="button"
              @click="handlePaginate(pagination.prev_page_url)"
              :disabled="!pagination.prev_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                pagination.prev_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Prev
            </button>

            <div class="px-3 py-1 text-[11px] text-gray-500">
              Halaman
              <span class="font-semibold text-gray-800">{{ filters.page }}</span>
            </div>

            <button
              type="button"
              @click="handlePaginate(pagination.next_page_url)"
              :disabled="!pagination.next_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                pagination.next_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Next
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Approval Confirmation Dialog -->
    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="showApprovalDialog = $event"
      @cancel="handleApprovalCancel"
      @confirm="handleApprovalConfirm"
    />

    <!-- Rejection Confirmation Dialog -->
    <RejectionConfirmationDialog
      :is-open="showRejectionDialog"
      :require-reason="true"
      @update:open="showRejectionDialog = $event"
      @cancel="handleRejectionCancel"
      @confirm="handleRejectionConfirm"
    />

    <!-- Passcode Verification Dialog -->
    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="showPasscodeDialog = $event"
      @cancel="handlePasscodeCancel"
      @verified="handlePasscodeVerified"
    />

    <!-- Success Dialog -->
    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="userName"
      document-type="Payment Voucher"
      @update:open="showSuccessDialog = $event"
      @close="handleSuccessClose"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { TicketPercent } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import PaymentVoucherApprovalFilter from "@/components/approval/PaymentVoucherApprovalFilter.vue";
import PaymentVoucherApprovalTable from "@/components/approval/PaymentVoucherApprovalTable.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import { useApi } from "@/composables/useApi";
import { getApprovalButtonClass } from "@/lib/status";

defineOptions({ layout: AppLayout });

// Props
const props = defineProps<{
  departments: Array<{ id: number; name: string }>;
  userRole: string;
}>();

// Initialize API composable
const { get, post } = useApi();

// Reactive data
const paymentVouchers = ref<any[]>([]);
const departments = ref(props.departments);
const loading = ref(false);
const selectedPaymentVouchers = ref<number[]>([]);
const pagination = ref<any>(null);
const userRole = ref(props.userRole);
const userName = ref("");

// Filters
const filters = ref({
  search: "",
  department_id: "",
  status: "",
  per_page: 10,
  page: 1,
});

// Debounced auto-fetch on filters change
let _filtersTimer: number | undefined;
watch(
  filters,
  () => {
    if (_filtersTimer) window.clearTimeout(_filtersTimer);
    _filtersTimer = window.setTimeout(() => {
      fetchPaymentVouchers();
    }, 300);
  },
  { deep: true }
);

// Columns configuration - Default columns for approval view
const columns = ref([
  { key: "no_pv", label: "No. PV", checked: true, sortable: false },
  { key: "reference_number", label: "Nomor Referensi Dokumen", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: true, sortable: false },
  { key: "bisnis_partner", label: "Bisnis Partner", checked: true, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  { key: "perihal", label: "Perihal", checked: false, sortable: false },
  { key: "department", label: "Department", checked: false, sortable: false },
  {
    key: "metode_pembayaran",
    label: "Metode Pembayaran",
    checked: false,
    sortable: false,
  },
  { key: "grand_total", label: "Grand Total", checked: false, sortable: true },
  { key: "nama_rekening", label: "Nama Rekening", checked: false, sortable: false },
  { key: "no_rekening", label: "No. Rekening", checked: false, sortable: false },
  { key: "no_kartu_kredit", label: "No. Kartu Kredit", checked: false, sortable: false },
  { key: "no_giro", label: "No. Giro", checked: false, sortable: false },
  { key: "tanggal_giro", label: "Tanggal Giro", checked: false, sortable: true },
  { key: "tanggal_cair", label: "Tanggal Cair", checked: false, sortable: true },
  { key: "keterangan", label: "Keterangan", checked: false, sortable: false },
  { key: "total", label: "Total", checked: false, sortable: true },
  { key: "diskon", label: "Diskon", checked: false, sortable: true },
  { key: "ppn", label: "PPN", checked: false, sortable: false },
  { key: "ppn_nominal", label: "PPN Nominal", checked: false, sortable: true },
  { key: "pph_nominal", label: "PPH Nominal", checked: false, sortable: true },
  { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
]);

// Status counts
const pendingCount = ref(0);
const approvedCount = ref(0);
const rejectedCount = ref(0);

// Mobile card menu state
const mobileMenuPvId = ref<number | null>(null);

// Dialog states
const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const successAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const pendingAction = ref<{
  type: "bulk" | "single";
  action: "verify" | "validate" | "approve" | "reject";
  ids: number[];
  singleItem?: any;
  reason?: string;
} | null>(null);

// Computed
const breadcrumbs = computed(() => [
  { label: "Home", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Payment Voucher" },
]);

function formatCurrency(amount: number) {
  if (amount === null || amount === undefined) return "-";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

function getDisplayGrandTotal(row: any): number {
  const tipe = String(row?.tipe_pv || '').toLowerCase();
  let val: any;
  if (tipe === 'manual' || tipe === 'pajak') {
    val = row?.nominal ?? row?.grand_total;
  } else if (tipe === 'lainnya') {
    val = row?.memo_cicilan ?? row?.grand_total;
  } else if (tipe === 'anggaran') {
    val = row?.grand_total ?? row?.nominal;
  } else {
    val = row?.grand_total;
  }
  const num = Number(val ?? 0);
  return isNaN(num) ? 0 : num;
}

function getApprovalButtonClassForTemplate(action: string) {
  return getApprovalButtonClass(action);
}

const bulkActionLabel = computed(() => {
  const map: Record<string, string> = {
    verify: "Verifikasi",
    validate: "Validasi",
    approve: "Setujui",
  };
  return selectedPaymentVouchers.value.length > 0 ? map[bulkActionType.value] : "Proses";
});

const bulkActionType = computed<"verify" | "validate" | "approve">(() => {
  if (selectedPaymentVouchers.value.length === 0) return "approve";

  const selectedRows = (paymentVouchers.value || []).filter((pv: any) =>
    selectedPaymentVouchers.value.includes(pv.id)
  );
  const firstStatus: string | undefined = selectedRows[0]?.status;
  const firstType: string | undefined = selectedRows[0]?.tipe_pv;
  const role = userRole.value;

  let mappedAction: "verify" | "validate" | "approve" = "verify";

  // Payment Voucher workflow: Kabag verify, Direksi approve
  if (role === "Kabag") {
    mappedAction = "verify"; // Kabag verifies
  } else if (role === "Kadiv") {
    // Kadiv validates Pajak after Kabag verification
    if (firstStatus === "Verified" && firstType === "Pajak") {
      mappedAction = "validate";
    } else {
      mappedAction = "verify";
    }
  } else if (role === "Direksi") {
    mappedAction = "approve"; // Direksi approves
  } else if (role === "Admin") {
    // Admin bypass: determine appropriate action based on status
    if (firstStatus === "In Progress") mappedAction = "verify";
    else if (firstStatus === "Verified") mappedAction = (firstType === "Pajak") ? "validate" : "approve";
    else mappedAction = "approve"; // fallback for other statuses
  }

  return mappedAction;
});

// Methods
const fetchPaymentVouchers = async () => {
  loading.value = true;
  try {
    const queryParams = new URLSearchParams();
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) queryParams.append(key, value.toString());
    });

    // Include search columns for dynamic search only if there's a search term
    if (filters.value.search) {
      const selectedColumnKeys = columns.value
        .filter((col) => col.checked)
        .map((col) => col.key);

      if (selectedColumnKeys.length > 0) {
        queryParams.set("search_columns", selectedColumnKeys.join(","));
      }
    }

    const data = await get(`/api/approval/payment-vouchers?${queryParams}`);

    paymentVouchers.value = data.data || [];
    pagination.value = data.pagination || null;

    // Update counts
    pendingCount.value = data.counts?.pending || 0;
    approvedCount.value = data.counts?.approved || 0;
    rejectedCount.value = data.counts?.rejected || 0;
  } catch (error) {
    console.error("Error fetching memo pembayarans:", error);
  } finally {
    loading.value = false;
  }
};

// Mobile card three-dots menu handlers
function toggleMobileMenu(pvId: number) {
  mobileMenuPvId.value = mobileMenuPvId.value === pvId ? null : pvId;
}

function handleMobileAction(action: "detail" | "download" | "log", row: any) {
  mobileMenuPvId.value = null;
  if (!row?.id) return;

  if (action === "detail") {
    router.visit(`/approval/payment-vouchers/${row.id}/detail`);
  } else if (action === "download") {
    window.open(`/payment-voucher/${row.id}/download`, "_blank");
  } else if (action === "log") {
    router.visit(`/approval/payment-vouchers/${row.id}/log`);
  }
}

const fetchDepartments = async () => {
  try {
    const data = await get("/api/departments");
    departments.value = data.data || [];
  } catch (error) {
    console.error("Error fetching departments:", error);
  }
};

// Event handlers
const handleFilter = (newFilters: any) => {
  // Always use per_page for pagination
  const updated: any = { ...filters.value, ...newFilters, page: 1 };
  if (Object.prototype.hasOwnProperty.call(newFilters, "entriesPerPage")) {
    updated.per_page = newFilters.entriesPerPage;
    delete updated.entriesPerPage;
  }

  // Handle search properly - always include search field
  if (Object.prototype.hasOwnProperty.call(newFilters, "search")) {
    updated.search = newFilters.search || "";
  }

  // Handle other filters - remove empty values (but keep search field)
  Object.keys(updated).forEach((key) => {
    if (
      key !== "search" &&
      (updated[key] === "" || updated[key] === null || updated[key] === undefined)
    ) {
      delete updated[key];
    }
  });

  filters.value = updated;
};

const resetFilters = () => {
  filters.value = {
    search: "",
    department_id: "",
    status: "",
    per_page: 10,
    page: 1,
  };
  fetchPaymentVouchers();
};

const updateEntriesPerPage = (perPage: number) => {
  filters.value.per_page = perPage;
  filters.value.page = 1;
};

const updateColumns = (newColumns: any[]) => {
  columns.value = newColumns;
};

const handleSelect = (selectedIds: number[]) => {
  selectedPaymentVouchers.value = selectedIds;
};

const handlePaginate = (url: string) => {
  if (url) {
    // Extract page number from URL if needed, or use the URL directly
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get("page");
    if (page) {
      filters.value.page = parseInt(page);
    }
    fetchPaymentVouchers();
  }
};

const handleAction = async (actionData: any) => {
  const { action } = actionData;
  const row =
    actionData.row || paymentVouchers.value.find((pv: any) => pv.id === actionData.id);

  switch (action) {
    case "detail":
      router.visit(`/approval/payment-vouchers/${row.id}/detail`);
      break;
    case "log":
      router.visit(`/approval/payment-vouchers/${row.id}/log`);
      break;
    case "download":
      window.open(`/payment-voucher/${row.id}/download`, "_blank");
      break;
    case "verify":
      pendingAction.value = {
        type: "single",
        action: "verify",
        ids: [row.id],
        singleItem: row,
      };
      showApprovalDialog.value = true;
      break;

    case "approve": {
      // Map generic approve action to correct step based on user role
      const role = userRole.value;
      let mappedAction: "verify" | "validate" | "approve" = "approve";

      // Payment Voucher workflow: Kabag/Kadiv verify, Direksi approve
      if (role === "Kabag") {
        mappedAction = "verify"; // Kabag verifies
      } else if (role === "Kadiv") {
        if (row.status === "Verified" && row.tipe_pv === "Pajak") {
          mappedAction = "validate"; // Kadiv validates Pajak/Manual
        } else {
          mappedAction = "verify";
        }
      } else if (role === "Direksi") {
        mappedAction = "approve"; // Direksi approves
      } else if (role === "Admin") {
        // Admin bypass logic - determine appropriate action based on current status
        if (row.status === "In Progress") {
          mappedAction = "verify"; // Verify first
        } else if (row.status === "Verified") {
          mappedAction = (row.tipe_pv === "Pajak") ? "validate" : "approve"; // Validate for Pajak only, else approve
        } else {
          mappedAction = "approve"; // fallback
        }
      }

      pendingAction.value = {
        type: "single",
        action: mappedAction,
        ids: [row.id],
        singleItem: row,
      };
      showApprovalDialog.value = true;
      break;
    }
    case "reject":
      pendingAction.value = {
        type: "single",
        action: "reject",
        ids: [row.id],
        singleItem: row,
      };
      showRejectionDialog.value = true;
      break;
  }
};

const handleBulkApprove = () => {
  if (selectedPaymentVouchers.value.length === 0) return;

  // Determine action based on user role
  const role = userRole.value;
  let mappedAction: "verify" | "validate" | "approve" = "approve";

  // Payment Voucher workflow: Kabag/Kadiv verify, Direksi approve
  if (role === "Kabag") {
    mappedAction = "verify"; // Kabag verifies (In Progress -> Verified)
  } else if (role === "Kadiv") {
    // If any selected is Verified and of Pajak, perform validate; else verify
    const selectedRows = (paymentVouchers.value || []).filter((pv: any) => selectedPaymentVouchers.value.includes(pv.id));
    const hasValidate = selectedRows.some((r: any) => r.status === "Verified" && r.tipe_pv === "Pajak");
    mappedAction = hasValidate ? "validate" : "verify";
  } else if (role === "Direksi") {
    mappedAction = "approve"; // Direksi approves (Verified -> Approved)
  } else if (role === "Admin") {
    // Admin can do any action, default to approve
    const selectedRows = (paymentVouchers.value || []).filter((pv: any) =>
      selectedPaymentVouchers.value.includes(pv.id)
    );

    const hasInProgress = selectedRows.some((r: any) => r.status === "In Progress");
    const hasPajakVerified = selectedRows.some(
      (r: any) => r.status === "Verified" && r.tipe_pv === "Pajak"
    );

    if (hasInProgress) {
      mappedAction = "verify";
    } else if (hasPajakVerified) {
      mappedAction = "validate";
    } else {
      mappedAction = "approve";
    }
  }

  pendingAction.value = {
    type: "bulk",
    action: mappedAction,
    ids: [...selectedPaymentVouchers.value],
  };
  showApprovalDialog.value = true;
};

const handleBulkReject = () => {
  if (selectedPaymentVouchers.value.length === 0) return;

  pendingAction.value = {
    type: "bulk",
    action: "reject",
    ids: [...selectedPaymentVouchers.value],
  };
  showRejectionDialog.value = true;
};

// Dialog event handlers
const handleApprovalCancel = () => {
  showApprovalDialog.value = false;
  pendingAction.value = null;
};

const handleApprovalConfirm = () => {
  if (!pendingAction.value) return;
  showApprovalDialog.value = false;
  passcodeAction.value = pendingAction.value.action;
  showPasscodeDialog.value = true;
};

const handleRejectionCancel = () => {
  showRejectionDialog.value = false;
  pendingAction.value = null;
};

const handleRejectionConfirm = (reason: string) => {
  if (!pendingAction.value) return;
  pendingAction.value.reason = reason;
  showRejectionDialog.value = false;
  passcodeAction.value = "reject";
  showPasscodeDialog.value = true;
};

const handlePasscodeCancel = () => {
  showPasscodeDialog.value = false;
  pendingAction.value = null;
};

const handlePasscodeVerified = async () => {
  if (!pendingAction.value) return;
  try {
    if (pendingAction.value.action === "verify") {
      if (pendingAction.value.type === "bulk") {
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/payment-vouchers/${id}/verify`);
        }
      } else {
        await post(`/api/approval/payment-vouchers/${pendingAction.value.ids[0]}/verify`);
      }
    } else if (pendingAction.value.action === "validate") {
      if (pendingAction.value.type === "bulk") {
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/payment-vouchers/${id}/validate`);
        }
      } else {
        await post(`/api/approval/payment-vouchers/${pendingAction.value.ids[0]}/validate`);
      }
    } else if (pendingAction.value.action === "approve") {
      if (pendingAction.value.type === "bulk") {
        await post(`/api/approval/payment-vouchers/bulk-approve`, {
          pv_ids: pendingAction.value.ids,
        });
      } else {
        await post(
          `/api/approval/payment-vouchers/${pendingAction.value.ids[0]}/approve`
        );
      }
    } else {
      if (pendingAction.value.type === "bulk") {
        await post(`/api/approval/payment-vouchers/bulk-reject`, {
          pv_ids: pendingAction.value.ids,
          reason: pendingAction.value.reason || "",
        });
      } else {
        await post(
          `/api/approval/payment-vouchers/${pendingAction.value.ids[0]}/reject`,
          {
            reason: pendingAction.value.reason || "",
          }
        );
      }
    }

    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;

    await fetchPaymentVouchers();
    selectedPaymentVouchers.value = selectedPaymentVouchers.value.filter(
      (id: number) => !pendingAction.value!.ids.includes(id)
    );
  } catch (error) {
    console.error(`Error ${pendingAction.value.action}ing payment vouchers:`, error);
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
};

const handleSuccessClose = () => {
  showSuccessDialog.value = false;
};

// Selectable statuses depend on role based on new workflow
const selectableStatuses = ref<string[]>(["In Progress"]);

function refreshSelectableStatuses() {
  const role = userRole.value;
  const newStatuses: string[] = [];

  if (role === "Admin") {
    newStatuses.push("In Progress", "Verified"); // Admin can act on all
  } else if (role === "Kabag") {
    newStatuses.push("In Progress"); // Kabag can verify (In Progress -> Verified)
  } else if (role === "Kadiv") {
    // Kadiv can verify items still In Progress, and can validate Pajak/Manual at Verified
    newStatuses.push("In Progress", "Verified");
  } else if (role === "Direksi") {
    // Direksi can approve PV after Verified; for Pajak, can also approve after Validated
    newStatuses.push("Verified", "Validated");
  } else {
    newStatuses.push("In Progress"); // default conservative
  }

  selectableStatuses.value = newStatuses;
}

// Function to check if a specific row is selectable given row details and current user role
function isRowSelectableForRole(row: any): boolean {
  const role = userRole.value;
  const status = row.status;
  const tipe = row.tipe_pv;

  if (role === "Admin") {
    // Admin bypass: dapat memproses semua status yang ada di selectableStatuses
    return selectableStatuses.value.includes(status);
  }

  if (role === "Kabag") {
    // Kabag can verify Payment Vouchers with status "In Progress"
    return status === "In Progress";
  }

  if (role === "Kadiv") {
    // Kadiv can verify when In Progress
    if (status === "In Progress") return true;
    // Kadiv can validate Pajak that have been verified by Kabag
    if (status === "Verified" && tipe === "Pajak") return true;
    return false;
  }

  if (role === "Direksi") {
    // Direksi can approve when:
    // - status "Verified" (default PV)
    // - status "Validated" for tipe "Pajak"
    if (status === "Verified") return true;
    if (status === "Validated" && tipe === "Pajak") return true;
    return false;
  }

  return false; // Role lain tidak bisa melakukan approval
}

// Get user info
const page = usePage();
const user = page.props.auth?.user;
if (user) {
  userName.value = user.name || "User";
}

// Lifecycle
onMounted(async () => {
  refreshSelectableStatuses();
  await Promise.all([fetchPaymentVouchers(), fetchDepartments()]);

  // Check for auto passcode dialog after redirect from passcode creation
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("auto_passcode_dialog") === "1") {
    const actionDataParam = urlParams.get("action_data");
    if (actionDataParam) {
      try {
        const actionData = JSON.parse(decodeURIComponent(actionDataParam));
        pendingAction.value = actionData;
        passcodeAction.value = actionData.action;
        showPasscodeDialog.value = true;

        // Clean up URL parameters
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete("auto_passcode_dialog");
        newUrl.searchParams.delete("action_data");
        window.history.replaceState({}, "", newUrl.toString());
      } catch (error) {
        console.error("Error parsing action data:", error);
      }
    }
  }
});
</script>
