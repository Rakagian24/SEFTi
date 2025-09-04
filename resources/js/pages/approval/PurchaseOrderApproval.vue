<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Purchase Order Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <ShoppingCart class="w-4 h-4 mr-1" />
            Dokumen Purchase Order yang menunggu persetujuan
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
          <!-- Selected Count -->
          <div class="text-sm text-gray-600">
            <span v-if="selectedPOs.length > 0" class="font-medium text-blue-600">
              {{ selectedPOs.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>

          <!-- Approve Button -->
          <button
            @click="handleBulkApprove"
            :disabled="selectedPOs.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedPOs.length > 0
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
            :disabled="selectedPOs.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedPOs.length > 0
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
      <PurchaseOrderApprovalFilter
        :filters="filters"
        :departments="departments"
        :perihals="perihals"
        :columns="columns"
        :entries-per-page="filters.per_page || 10"
        @filter="handleFilter"
        @reset="resetFilters"
        @update:entries-per-page="updateEntriesPerPage"
        @update:columns="updateColumns"
      />

      <!-- Table Component -->
      <PurchaseOrderApprovalTable
        :data="purchaseOrders"
        :loading="loading"
        :selected="selectedPOs"
        :pagination="pagination"
        :columns="columns"
        :selectable-statuses="selectableStatuses"
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
      document-type="Purchase Order"
      @update:open="showSuccessDialog = $event"
      @close="handleSuccessClose"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { ShoppingCart } from "lucide-vue-next";
import { useApi } from "@/composables/useApi";
import PurchaseOrderApprovalFilter from "@/components/approval/PurchaseOrderApprovalFilter.vue";
import PurchaseOrderApprovalTable from "@/components/approval/PurchaseOrderApprovalTable.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Purchase Order" },
];

// Initialize API composable
const { get, post } = useApi();

// Data
const purchaseOrders = ref<any[]>([]);
const departments = ref<any[]>([]);
const perihals = ref<any[]>([]);
const loading = ref(false);
const selectedPOs = ref<number[]>([]);

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

// User info
const userName = ref("");
const userRole = ref<string>("");

// Pagination
const pagination = ref<any>(null);

// Filters
const filters = ref({
  tanggal_start: "",
  tanggal_end: "",
  department_id: "",
  status: "",
  perihal_id: "",
  metode_pembayaran: "",
  search: "",
  page: 1,
  per_page: 10,
});

// Counts
const pendingCount = ref(0);
const approvedCount = ref(0);
const rejectedCount = ref(0);

// Columns configuration
const columns = ref([
  { key: "no_po", label: "No. PO", checked: true, sortable: false },
  { key: "no_invoice", label: "No. Invoice", checked: false, sortable: false },
  { key: "tipe_po", label: "Tipe PO", checked: false, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: false, sortable: true },
  { key: "department", label: "Departemen", checked: false, sortable: false },
  { key: "perihal", label: "Perihal", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: false, sortable: false },
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
]);

// Methods
const fetchPurchaseOrders = async () => {
  loading.value = true;
  try {
    const queryParams = new URLSearchParams();
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) queryParams.append(key, value.toString());
    });

    // Sertakan kolom yang sedang ditampilkan (checked) untuk pencarian dinamis
    const selectedColumnKeys = (columns.value || [])
      .filter((c: any) => c && c.checked)
      .map((c: any) => c.key);

    if (selectedColumnKeys.length > 0) {
      queryParams.append("search_columns", selectedColumnKeys.join(","));
    }

    const data = await get(`/api/approval/purchase-orders?${queryParams}`);

    purchaseOrders.value = data.data || [];
    pagination.value = data.pagination || null;

    // Update counts
    pendingCount.value = data.counts?.pending || 0;
    approvedCount.value = data.counts?.approved || 0;
    rejectedCount.value = data.counts?.rejected || 0;
  } catch (error) {
    console.error("Error fetching purchase orders:", error);
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

const fetchPerihals = async () => {
  try {
    const data = await get("/api/perihals");
    perihals.value = data.data || [];
  } catch (error) {
    console.error("Error fetching perihals:", error);
  }
};

// Event handlers
const handleFilter = (newFilters: any) => {
  const updated: any = { ...filters.value, ...newFilters, page: 1 };

  // Normalisasi entries_per_page -> per_page jika datang dari komponen filter
  if (Object.prototype.hasOwnProperty.call(newFilters, "entries_per_page")) {
    updated.per_page = newFilters.entries_per_page;
    delete updated.entries_per_page;
  }

  filters.value = updated;
  fetchPurchaseOrders();
};

const resetFilters = () => {
  filters.value = {
    tanggal_start: "",
    tanggal_end: "",
    department_id: "",
    status: "",
    perihal_id: "",
    metode_pembayaran: "",
    search: "",
    page: 1,
    per_page: 10,
  };
  fetchPurchaseOrders();
};

const updateEntriesPerPage = (value: number) => {
  filters.value.per_page = value;
  filters.value.page = 1;
  fetchPurchaseOrders();
};

const updateColumns = (newColumns: any[]) => {
  columns.value = newColumns;
};

// Selectable statuses depend on role
const selectableStatuses = ref<string[]>(["In Progress"]);

function refreshSelectableStatuses() {
  const role = userRole.value;
  if (role === "Kabag" || role === "Kepala Toko") {
    // Can only verify In Progress items
    selectableStatuses.value = ["In Progress"];
  } else if (role === "Kadiv") {
    // Can only validate Verified items
    selectableStatuses.value = ["Verified"];
  } else if (role === "Direksi") {
    // Can approve Validated items (normal flow) or Verified items (HG/Zi&Glo flow)
    selectableStatuses.value = ["Validated", "Verified"];
  } else if (role === "Admin") {
    // Admin can do everything
    selectableStatuses.value = ["In Progress", "Verified", "Validated"];
  } else {
    selectableStatuses.value = ["In Progress"]; // default conservative
  }
}

const handleSelect = (selectedIds: number[]) => {
  selectedPOs.value = selectedIds;
};

// Determine if a row is selectable based on current user's role
// Deprecated helper – selection now controlled via selectableStatuses prop

const handleBulkApprove = () => {
  if (selectedPOs.value.length === 0) return;

  // Determine action based on user role and selected rows' status
  const selectedRows = (purchaseOrders.value || []).filter((po: any) =>
    selectedPOs.value.includes(po.id)
  );
  const firstStatus: string | undefined = selectedRows[0]?.status;
  const role = userRole.value;

  let mappedAction: "verify" | "validate" | "approve" = "verify";

  // Map action based on user role and status
  if (role === "Kabag" || role === "Kepala Toko") {
    // Can only verify In Progress items
    mappedAction = "verify";
  } else if (role === "Kadiv") {
    // Can only validate Verified items
    mappedAction = "validate";
  } else if (role === "Direksi") {
    // Can approve Validated items (normal flow) or Verified items (HG/Zi&Glo flow)
    if (firstStatus === "Validated") {
      mappedAction = "approve";
    } else if (firstStatus === "Verified") {
      // Check if any selected item is from HG/Zi&Glo
      const hasHGOrZiGlo = selectedRows.some((po: any) =>
        ["Human Greatness", "Zi&Glo"].includes(po.department?.name)
      );
      mappedAction = hasHGOrZiGlo ? "approve" : "validate";
    }
  } else if (role === "Admin") {
    // Admin can do any action based on status
    if (firstStatus === "In Progress") mappedAction = "verify";
    else if (firstStatus === "Verified") mappedAction = "validate";
    else if (firstStatus === "Validated") mappedAction = "approve";
  }

  pendingAction.value = {
    type: "bulk",
    action: mappedAction,
    ids: [...selectedPOs.value],
  };
  showApprovalDialog.value = true;
};

const handleBulkReject = () => {
  if (selectedPOs.value.length === 0) return;

  pendingAction.value = {
    type: "bulk",
    action: "reject",
    ids: [...selectedPOs.value],
  };
  showRejectionDialog.value = true;
};

const handleAction = async (actionData: any) => {
  const { action, row } = actionData;

  switch (action) {
    case "detail":
      router.visit(`/approval/purchase-orders/${row.id}`);
      break;
    case "log":
      router.visit(`/approval/purchase-orders/${row.id}/log`);
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
      // Map generic approve action to correct step based on row status & department
      const dept = row?.department?.name;
      let mappedAction: "verify" | "validate" | "approve" = "approve";
      if (row.status === "In Progress") {
        mappedAction = "verify";
      } else if (row.status === "Verified") {
        // Human Greatness & Zi&Glo can be approved by Direksi after Verified
        if (["Human Greatness", "Zi&Glo"].includes(dept)) {
          mappedAction = "approve";
        } else {
          mappedAction = "validate";
        }
      } else if (row.status === "Validated") {
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

const handlePaginate = (url: string) => {
  if (url) {
    try {
      const urlObj = new URL(url);
      const page = urlObj.searchParams.get("page");
      if (page) {
        filters.value.page = parseInt(page);
        fetchPurchaseOrders();
      }
    } catch (error) {
      console.error("Error parsing pagination URL:", error);
    }
  }
};

// Dialog event handlers
const handleApprovalCancel = () => {
  showApprovalDialog.value = false;
  pendingAction.value = null;
};

const handleApprovalConfirm = () => {
  if (!pendingAction.value) return;

  // Close approval dialog and show passcode verification
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

  // Store the reason in pending action
  pendingAction.value.reason = reason;

  // Close rejection dialog and show passcode verification
  showRejectionDialog.value = false;
  passcodeAction.value = "reject";
  showPasscodeDialog.value = true;
};

// Passcode dialog event handlers
const handlePasscodeCancel = () => {
  showPasscodeDialog.value = false;
  pendingAction.value = null;
};

const handlePasscodeVerified = async () => {
  if (!pendingAction.value) return;

  try {
    if (pendingAction.value.action === "verify") {
      if (pendingAction.value.type === "bulk") {
        // Bulk verify not supported yet, handle individually
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/purchase-orders/${id}/verify`);
        }
      } else {
        await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/verify`);
      }
    } else if (pendingAction.value.action === "validate") {
      if (pendingAction.value.type === "bulk") {
        // Bulk validate not supported yet, handle individually
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/purchase-orders/${id}/validate`);
        }
      } else {
        await post(
          `/api/approval/purchase-orders/${pendingAction.value.ids[0]}/validate`
        );
      }
    } else if (pendingAction.value.action === "approve") {
      if (pendingAction.value.type === "bulk") {
        await post("/api/approval/purchase-orders/bulk-approve", {
          po_ids: pendingAction.value.ids,
        });
      } else {
        await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/approve`);
      }
    } else {
      if (pendingAction.value.type === "bulk") {
        await post("/api/approval/purchase-orders/bulk-reject", {
          po_ids: pendingAction.value.ids,
          reason: pendingAction.value.reason || "",
        });
      } else {
        await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/reject`, {
          reason: pendingAction.value.reason || "",
        });
      }
    }

    // Show success dialog
    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;

    // Update data in background
    await fetchPurchaseOrders();
    selectedPOs.value = selectedPOs.value.filter(
      (id: number) => !pendingAction.value!.ids.includes(id)
    );
  } catch (error) {
    console.error(`Error ${pendingAction.value.action}ing POs:`, error);
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
};

// Success dialog event handler
const handleSuccessClose = () => {
  showSuccessDialog.value = false;
};

// Get user info
const page = usePage();
const user = page.props.auth?.user;
if (user) {
  userName.value = user.name || "User";
  userRole.value = (user as any).role?.name || "";
}
refreshSelectableStatuses();

// Debug function to test API
const testApprovalAPI = async () => {
  try {
    await get("/api/debug/approval-test");
  } catch (error) {
    console.error("Debug API error:", error);
  }
};

// Lifecycle
onMounted(async () => {
  // Test the debug API first
  await testApprovalAPI();

  await Promise.all([fetchPurchaseOrders(), fetchDepartments(), fetchPerihals()]);
});
</script>
