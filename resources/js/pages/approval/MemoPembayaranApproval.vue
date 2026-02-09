<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-6 hidden items-center justify-between md:flex">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Memo Pembayaran Approval</h1>
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Dokumen Memo Pembayaran yang menunggu persetujuan
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
          <!-- Selected Count -->
          <div class="text-sm text-gray-600">
            <span v-if="selectedMemos.length > 0" class="font-medium text-blue-600">
              {{ selectedMemos.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>

          <!-- Primary Process Button (dynamic: Verifikasi/Validasi/Setujui) -->
          <button
            @click="handleBulkApprove"
            :disabled="selectedMemos.length === 0"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
              selectedMemos.length > 0
                ? getApprovalButtonClassForTemplate(bulkActionType)
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            :disabled="selectedMemos.length === 0"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
              selectedMemos.length > 0
                ? 'bg-white text-red-600 border border-red-600 hover:bg-red-50'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">Memo Pembayaran Approval</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <WalletCards class="mr-1 h-3 w-3" />
          Dokumen Memo Pembayaran yang menunggu persetujuan
        </div>
      </div>

      <!-- Mobile actions: select all + selected count + approve/reject -->
      <div class="mb-4 flex items-center justify-between gap-2 md:hidden">
        <div class="flex flex-col text-xs text-gray-600">
          <button
            type="button"
            class="mb-1 self-start rounded-full border border-blue-500 px-2 py-0.5 text-[11px] font-medium text-blue-600"
            @click="toggleMobileSelectAll"
          >
            {{ areAllMobileRowsSelected() ? 'Batal pilih semua' : 'Pilih semua' }}
          </button>
          <div>
            <span v-if="selectedMemos.length > 0" class="font-semibold text-blue-600">
              {{ selectedMemos.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="handleBulkApprove"
            :disabled="selectedMemos.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selectedMemos.length > 0
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
            :disabled="selectedMemos.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selectedMemos.length > 0
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

      <!-- Desktop / Tablet: Filter + Table -->
      <div class="hidden md:block">
        <!-- Filter Component -->
        <MemoPembayaranApprovalFilter
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
        <MemoPembayaranApprovalTable
          :data="memoPembayarans"
          :loading="loading"
          :selected="selectedMemos"
          :pagination="pagination"
          :columns="columns"
          :selectable-statuses="selectableStatuses"
          :is-row-selectable="isRowSelectableForRole"
          @select="handleSelect"
          @action="handleAction"
          @paginate="handlePaginate"
        />

        <StatusLegend entity="Memo Pembayaran" />
      </div>

      <!-- Mobile: Card list -->
      <div class="mt-4 md:hidden">
        <!-- Simple search -->
        <div class="mb-4">
          <input
            v-model="filters.search"
            type="text"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="Cari No. MB / No. PO / Supplier"
            @keyup.enter="fetchMemoPembayarans()"
            @blur="fetchMemoPembayarans()"
          />
        </div>

        <div v-if="loading" class="space-y-3">
          <div
            v-for="i in 3"
            :key="i"
            class="w-full rounded-xl bg-white p-3 text-left shadow-sm animate-pulse"
          >
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
            v-if="!memoPembayarans || memoPembayarans.length === 0"
            class="py-8 text-center text-sm text-gray-500"
          >
            Tidak ada Memo Pembayaran yang menunggu persetujuan.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="memo in memoPembayarans"
              :key="memo.id"
              class="w-full rounded-xl bg-white p-3 text-left shadow-sm active:bg-slate-50"
            >
              <div class="mb-1 flex items-start justify-between">
                <div class="flex items-center gap-2">
                  <input
                    v-if="isRowSelectableForRole(memo)"
                    type="checkbox"
                    :value="memo.id"
                    v-model="selectedMemos"
                    class="h-4 w-4 self-center rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500"
                    @click.stop
                  />
                  <div>
                    <div class="text-xs font-semibold text-gray-500">No. MB</div>
                    <div class="text-xs font-semibold text-gray-900">
                      {{ memo.no_mb || '-' }}
                    </div>
                  </div>
                </div>

                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                  :class="getStatusBadgeClassMobile(memo.status)"
                >
                  {{ memo.status || '-' }}
                </span>
              </div>

              <div class="mt-1 text-xs text-gray-500 truncate">
                {{ memo.perihal || '-' }}
              </div>

              <div class="mt-2 flex items-end justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="text-[11px] text-gray-500">Supplier</div>
                  <div class="truncate text-xs font-medium text-gray-900">
                    {{ getSupplierLabel(memo) }}
                  </div>
                </div>

                <div class="text-right">
                  <div class="text-[11px] text-gray-500">Grand Total</div>
                  <div class="text-sm font-semibold text-emerald-700">
                    {{ formatCurrency(memo.grand_total || 0) }}
                  </div>
                  <div class="mt-1 text-[11px] text-gray-400">
                    {{ memo.tanggal ? formatDate(memo.tanggal) : '-' }}
                  </div>
                </div>
              </div>

              <!-- Mobile card actions menu -->
              <div class="mt-2 flex justify-end">
                <div class="relative inline-block text-left">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                    @click.stop="toggleMobileMenu(memo.id)"
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
                    v-if="mobileMenuMemoId === memo.id"
                    class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-lg bg-white py-1 text-xs shadow-lg ring-1 ring-black/5"
                  >
                    <button
                      type="button"
                      class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                      @click.stop="handleAction({ action: 'detail', row: memo })"
                    >
                      Detail
                    </button>
                    <button
                      type="button"
                      class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                      @click.stop="handleAction({ action: 'download', row: memo })"
                    >
                      Download
                    </button>
                    <button
                      type="button"
                      class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                      @click.stop="handleAction({ action: 'log', row: memo })"
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
            v-if="pagination && memoPembayarans && memoPembayarans.length > 0"
            class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
          >
            <nav
              class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
              aria-label="Pagination"
            >
              <button
                type="button"
                @click="handlePaginate(pagination.prev_page_url)">
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
      document-type="Memo Pembayaran"
      @update:open="showSuccessDialog = $event"
      @close="handleSuccessClose"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { WalletCards } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import MemoPembayaranApprovalFilter from "@/components/approval/MemoPembayaranApprovalFilter.vue";
import MemoPembayaranApprovalTable from "@/components/approval/MemoPembayaranApprovalTable.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import { useApi } from "@/composables/useApi";
import { getApprovalButtonClass, getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";

defineOptions({ layout: AppLayout });

// Props
const props = defineProps<{
  departments: Array<{ id: number; name: string }>;
  userRole: string;
}>();

// Initialize API composable
const { get, post } = useApi();

// Reactive data
const memoPembayarans = ref<any[]>([]);
const departments = ref(props.departments);
const loading = ref(false);
const selectedMemos = ref<number[]>([]);
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

// Columns configuration - Default columns for approval view
const columns = ref([
  { key: "no_mb", label: "No. MB", checked: true, sortable: false },
  { key: "no_po", label: "No. PO", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: true, sortable: false },
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
  { label: "Memo Pembayaran" },
]);

function getApprovalButtonClassForTemplate(action: string) {
  return getApprovalButtonClass(action);
}

const bulkActionLabel = computed(() => {
  const map: Record<string, string> = {
    verify: "Verifikasi",
    validate: "Validasi",
    approve: "Setujui",
  };
  return selectedMemos.value.length > 0 ? map[bulkActionType.value] : "Proses";
});

const bulkActionType = computed<"verify" | "validate" | "approve">(() => {
  if (selectedMemos.value.length === 0) return "approve";

  const selectedRows = (memoPembayarans.value || []).filter((memo: any) =>
    selectedMemos.value.includes(memo.id)
  );
  const firstRow: any | undefined = selectedRows[0];
  const firstStatus: string | undefined = firstRow?.status;
  const role = userRole.value;

  let mappedAction: "verify" | "validate" | "approve" = "verify";

  if (role === "Kabag") {
    mappedAction = "approve";
  } else if (role === "Kepala Toko") {
    mappedAction = "verify";
  } else if (role === "Kadiv") {
    mappedAction = "approve";
  } else if (role === "Admin") {
    // Admin bypass: determine appropriate action based on status dan workflow
    if (firstStatus === "In Progress") {
      const creatorRole: string | undefined = firstRow?.creator?.role?.name;
      const dept: string | undefined = firstRow?.department?.name;

      if (
        creatorRole === "Staff Toko" &&
        dept !== "Zi&Glo" &&
        dept !== "Human Greatness"
      ) {
        // Staff Toko departemen biasa: butuh langkah verify dulu
        mappedAction = "verify";
      } else {
        // Untuk DM, Akunting, Zi&Glo/HG, dll: langsung approve
        mappedAction = "approve";
      }
    } else if (firstStatus === "Verified") {
      mappedAction = "approve";
    } else {
      mappedAction = "approve"; // fallback untuk status lain
    }
  }

  return mappedAction;
});

// Methods
const fetchMemoPembayarans = async () => {
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

    const data = await get(`/api/approval/memo-pembayarans?${queryParams}`);

    memoPembayarans.value = data.data || [];
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

function formatDate(date: string) {
  if (!date) return "-";
  try {
    return new Date(date).toLocaleDateString("id-ID", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  } catch {
    return date;
  }
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

function getSupplierLabel(row: any) {
  // Reuse the same supplier resolution logic as table component
  if (!row) return "-";

  // If supplier is nested object with nama_supplier / name / nama
  if (row.supplier && typeof row.supplier === "object") {
    return row.supplier.nama_supplier || row.supplier.name || row.supplier.nama || "-";
  }

  // If already a string
  if (typeof row.supplier === "string") {
    return row.supplier;
  }

  if (row.supplier_name) return row.supplier_name;

  return "-";
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
  fetchMemoPembayarans();
};

const resetFilters = () => {
  filters.value = {
    search: "",
    department_id: "",
    status: "",
    per_page: 10,
    page: 1,
  };
  fetchMemoPembayarans();
};

const updateEntriesPerPage = (perPage: number) => {
  filters.value.per_page = perPage;
  filters.value.page = 1;
  fetchMemoPembayarans();
};

const updateColumns = (newColumns: any[]) => {
  columns.value = newColumns;
};

const handleSelect = (selectedIds: number[]) => {
  selectedMemos.value = selectedIds;
};

// Mobile card helpers
const mobileMenuMemoId = ref<number | null>(null);

function toggleMobileMenu(memoId: number) {
  mobileMenuMemoId.value = mobileMenuMemoId.value === memoId ? null : memoId;
}

function getStatusBadgeClassMobile(status: string) {
  return getSharedStatusBadgeClass(status || "Draft");
}

function getMobileSelectableIds(): number[] {
  const currentRows = memoPembayarans.value || [];
  const statuses = selectableStatuses.value || [];
  return currentRows
    .filter((row: any) => {
      const status = row?.status;
      return status && statuses.includes(status) && isRowSelectableForRole(row);
    })
    .map((row: any) => row.id)
    .filter((id: any) => typeof id === "number");
}

function areAllMobileRowsSelected(): boolean {
  const selectableIds = getMobileSelectableIds();
  if (selectableIds.length === 0) return false;
  return selectableIds.every((id) => selectedMemos.value.includes(id));
}

function toggleMobileSelectAll() {
  const selectableIds = getMobileSelectableIds();
  if (selectableIds.length === 0) {
    selectedMemos.value = [];
    return;
  }
  if (selectableIds.every((id) => selectedMemos.value.includes(id))) {
    selectedMemos.value = selectedMemos.value.filter((id) => !selectableIds.includes(id));
  } else {
    selectedMemos.value = Array.from(new Set([...selectedMemos.value, ...selectableIds]));
  }
}

const handlePaginate = (url: string) => {
  if (url) {
    // Extract page number from URL if needed, or use the URL directly
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get("page");
    if (page) {
      filters.value.page = parseInt(page);
    }
    fetchMemoPembayarans();
  }
};

const handleAction = async (actionData: any) => {
  const { action } = actionData;
  const row =
    actionData.row || memoPembayarans.value.find((m: any) => m.id === actionData.id);

  switch (action) {
    case "detail":
      router.visit(`/approval/memo-pembayarans/${row.id}/detail`);
      break;
    case "log":
      router.visit(`/approval/memo-pembayaran/${row.id}/log`);
      break;
    case "download":
      window.open(`/memo-pembayaran/${row.id}/download`, "_blank");
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
    case "validate":
      pendingAction.value = {
        type: "single",
        action: "validate",
        ids: [row.id],
        singleItem: row,
      };
      showApprovalDialog.value = true;
      break;
    case "approve": {
      // Map generic approve action to correct step based on user role and new workflow
      const role = userRole.value;
      const creatorRole = row?.creator?.role?.name;
      const dept = row?.department?.name;
      let mappedAction: "verify" | "validate" | "approve" = "approve";

      if (role === "Kepala Toko") {
        // Kepala Toko:
        // - Departemen biasa: verify memo Staff Toko/Admin
        // - Zi&Glo/Human Greatness: langsung approve memo Staff Toko
        if (
          creatorRole === "Staff Toko" &&
          (dept === "Zi&Glo" || dept === "Human Greatness")
        ) {
          mappedAction = "approve";
        } else {
          mappedAction = "verify";
        }
      } else if (role === "Kadiv" || role === "Kabag") {
        // Kadiv dan Kabag bisa approve
        mappedAction = "approve";
      } else if (role === "Admin") {
        // Admin bypass logic - determine appropriate action based on current status
        if (row.status === "In Progress") {
          // Check if this should be verified first or approved directly
          if (
            creatorRole === "Staff Toko" &&
            dept !== "Zi&Glo" &&
            dept !== "Human Greatness"
          ) {
            mappedAction = "verify"; // Staff Toko needs verification first
          } else {
            mappedAction = "approve"; // Direct approval for others
          }
        } else if (row.status === "Verified") {
          mappedAction = "approve"; // Always approve verified memos
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
  if (selectedMemos.value.length === 0) return;

  // Determine action based on user role and new workflow
  // Use the same computed mapping shown in the button label to avoid mismatch
  const mappedAction: "verify" | "validate" | "approve" = bulkActionType.value;

  pendingAction.value = {
    type: "bulk",
    action: mappedAction,
    ids: [...selectedMemos.value],
  };
  showApprovalDialog.value = true;
};

const handleBulkReject = () => {
  if (selectedMemos.value.length === 0) return;

  pendingAction.value = {
    type: "bulk",
    action: "reject",
    ids: [...selectedMemos.value],
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
          await post(`/api/approval/memo-pembayarans/${id}/verify`);
        }
      } else {
        await post(`/api/approval/memo-pembayarans/${pendingAction.value.ids[0]}/verify`);
      }
    } else if (pendingAction.value.action === "validate") {
      if (pendingAction.value.type === "bulk") {
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/memo-pembayarans/${id}/validate`);
        }
      } else {
        await post(
          `/api/approval/memo-pembayarans/${pendingAction.value.ids[0]}/validate`
        );
      }
    } else if (pendingAction.value.action === "approve") {
      if (pendingAction.value.type === "bulk") {
        await post(`/api/approval/memo-pembayarans/bulk-approve`, {
          memo_ids: pendingAction.value.ids,
        });
      } else {
        await post(
          `/api/approval/memo-pembayarans/${pendingAction.value.ids[0]}/approve`
        );
      }
    } else {
      if (pendingAction.value.type === "bulk") {
        await post(`/api/approval/memo-pembayarans/bulk-reject`, {
          memo_ids: pendingAction.value.ids,
          reason: pendingAction.value.reason || "",
        });
      } else {
        await post(
          `/api/approval/memo-pembayarans/${pendingAction.value.ids[0]}/reject`,
          {
            reason: pendingAction.value.reason || "",
          }
        );
      }
    }

    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;

    await fetchMemoPembayarans();
    selectedMemos.value = selectedMemos.value.filter(
      (id: number) => !pendingAction.value!.ids.includes(id)
    );
  } catch (error) {
    console.error(`Error ${pendingAction.value.action}ing memos:`, error);
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
    newStatuses.push("In Progress", "Verified", "Validated", "Approved"); // Admin can act on all
  } else if (role === "Kepala Toko") {
    newStatuses.push("In Progress"); // Kepala Toko hanya bisa verify (In Progress -> Verified)
  } else if (role === "Kadiv") {
    newStatuses.push("In Progress", "Verified"); // Kadiv bisa approve In Progress (DM/Zi&Glo) dan Verified (Staff Toko)
  } else if (role === "Kabag") {
    newStatuses.push("In Progress"); // Kabag hanya bisa approve (In Progress -> Approved)
  } else {
    newStatuses.push("In Progress"); // default conservative
  }

  selectableStatuses.value = newStatuses;
}

// Function to check if a specific row is selectable given row details and current user role
function isRowSelectableForRole(row: any): boolean {
  const role = userRole.value;
  const creatorRole = row?.creator?.role?.name;
  const dept = row?.department?.name;

  if (role === "Admin") {
    // Admin bypass: dapat memproses semua status yang ada di selectableStatuses
    return selectableStatuses.value.includes(row.status);
  }

  if (role === "Kepala Toko") {
    // Kepala Toko bisa memproses:
    // - Verifikasi memo Staff Toko/Admin untuk departemen selain Zi&Glo/Human Greatness (handled via verify action)
    // - Menyetujui langsung memo Staff Toko untuk departemen Zi&Glo/Human Greatness (approve)
    if (row.status !== "In Progress") {
      return false;
    }

    // Staff Toko di semua departemen dapat diproses Kepala Toko (jenis aksi tergantung departemen & status)
    if (creatorRole === "Staff Toko") {
      return true;
    }

    // Admin hanya untuk departemen non Zi&Glo/Human Greatness
    if (
      creatorRole === "Admin" &&
      dept !== "Zi&Glo" &&
      dept !== "Human Greatness"
    ) {
      return true;
    }

    return false;
  }

  if (role === "Kadiv") {
    // Kadiv bisa approve:
    // 1. Memo Staff Toko yang sudah di-verify (status Verified)
    // 2. Memo Kepala Toko yang langsung Verified
    // 3. Memo Admin yang mengikuti flow multi-step (Verified -> Approved)
    // 3. Memo Staff Digital Marketing langsung (status In Progress)
    // 4. Memo dari departemen Zi&Glo langsung (status In Progress)
    if (
      row.status === "Verified" &&
      (creatorRole === "Staff Toko" ||
        creatorRole === "Kepala Toko" ||
        creatorRole === "Admin")
    ) {
      return true; // Staff Toko flow: setelah Kepala Toko verify, atau Kepala Toko langsung
    }
    if (
      row.status === "In Progress" &&
      creatorRole === "Staff Digital Marketing"
    ) {
      return true; // Digital Marketing flow: langsung approve
    }
    return false;
  }

  if (role === "Kabag") {
    // Kabag hanya bisa approve memo Staff Akunting & Finance
    if (row.status === "In Progress" && creatorRole === "Staff Akunting & Finance") {
      return true;
    }
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
  await Promise.all([fetchMemoPembayarans(), fetchDepartments()]);

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
