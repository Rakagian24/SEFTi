<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Memo Pembayaran Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
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

          <!-- Approve Button -->
          <button
            @click="handleBulkApprove"
            :disabled="selectedMemos.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedMemos.length > 0
                ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow-md'
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
            Setujui
          </button>

          <!-- Reject Button -->
          <button
            @click="handleBulkReject"
            :disabled="selectedMemos.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedMemos.length > 0
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
import { CreditCard } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import MemoPembayaranApprovalFilter from "@/components/approval/MemoPembayaranApprovalFilter.vue";
import MemoPembayaranApprovalTable from "@/components/approval/MemoPembayaranApprovalTable.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import { useApi } from "@/composables/useApi";

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
  { key: "detail_keperluan", label: "Detail Keperluan", checked: false, sortable: false },
  { key: "metode_pembayaran", label: "Metode Pembayaran", checked: false, sortable: false },
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

// Methods
const fetchMemoPembayarans = async () => {
  loading.value = true;
  try {
    const queryParams = new URLSearchParams();
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) queryParams.append(key, value.toString());
    });

    // Include search columns for dynamic search
    const selectedColumnKeys = columns.value
      .filter((col) => col.checked)
      .map((col) => col.key);

    if (selectedColumnKeys.length > 0) {
      queryParams.append("search_columns", selectedColumnKeys.join(","));
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
  const updated: any = { ...filters.value, ...newFilters, page: 1 };

  // Normalize entries_per_page -> per_page if coming from filter component
  if (Object.prototype.hasOwnProperty.call(newFilters, "entries_per_page")) {
    updated.per_page = newFilters.entries_per_page;
    delete updated.entries_per_page;
  }

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

const handlePaginate = (url: string) => {
  if (url) {
    // Extract page number from URL if needed, or use the URL directly
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get('page');
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
      router.visit(`/memo-pembayaran/${row.id}/log`);
      break;
    case "download":
      window.open(`/memo-pembayaran/${row.id}/download`, '_blank');
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
      let mappedAction: "verify" | "validate" | "approve" = "approve";

      if (role === "Kepala Toko") {
        // Kepala Toko hanya bisa verify
        mappedAction = "verify";
      } else if (role === "Kadiv" || role === "Kabag" || role === "Admin") {
        // Kadiv, Kabag, dan Admin bisa approve
        mappedAction = "approve";
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
  const role = userRole.value;
  let mappedAction: "verify" | "validate" | "approve" = "approve";

  // Map action based on user role + new workflow
  if (role === "Admin") {
    // Admin can do any action, default to approve
    mappedAction = "approve";
  } else if (role === "Kepala Toko") {
    // Kepala Toko hanya bisa verify (In Progress -> Verified)
    mappedAction = "verify";
  } else if (role === "Kadiv") {
    // Kadiv bisa approve (In Progress -> Approved untuk DM/Zi&Glo, Verified -> Approved untuk Staff Toko)
    mappedAction = "approve";
  } else if (role === "Kabag") {
    // Kabag hanya bisa approve (In Progress -> Approved)
    mappedAction = "approve";
  }

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

  // Debug log untuk troubleshooting (dapat dihapus setelah testing)
  // console.log('isRowSelectableForRole debug:', {
  //   role,
  //   rowStatus: row.status,
  //   creatorRole,
  //   department: dept,
  //   rowId: row.id
  // });

  // Workflow Multi-Level Approval Memo Pembayaran:
  // - Staff Toko: Kepala Toko(Verify) -> Kadiv(Approve)
  // - Staff Akunting & Finance: Kabag(Approve)
  // - Staff Digital Marketing: Kadiv(Approve)
  // - Departemen Zi&Glo: Kadiv(Approve)

  if (role === "Admin") {
    // Admin can do everything
    return true;
  }

  if (role === "Kepala Toko") {
    // Kepala Toko hanya bisa verify memo yang dibuat Staff Toko (bukan Zi&Glo)
    if (row.status === "In Progress" && creatorRole === "Staff Toko" && dept !== "Zi&Glo") {
      return true;
    }
    return false;
  }

  if (role === "Kadiv") {
    // Kadiv bisa approve:
    // 1. Memo Staff Toko yang sudah di-verify (status Verified)
    // 2. Memo Staff Digital Marketing langsung (status In Progress)
    // 3. Memo dari departemen Zi&Glo langsung (status In Progress)
    if (row.status === "Verified" && creatorRole === "Staff Toko") {
      return true; // Staff Toko flow: setelah Kepala Toko verify
    }
    if (row.status === "In Progress" && (creatorRole === "Staff Digital Marketing" || dept === "Zi&Glo")) {
      return true; // DM dan Zi&Glo flow: langsung approve
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
});
</script>
