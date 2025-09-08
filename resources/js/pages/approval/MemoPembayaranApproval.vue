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
        :entries-per-page="filters.per_page || 10"
        @filter="handleFilter"
        @reset="resetFilters"
        @update:entries-per-page="updateEntriesPerPage"
      />

      <!-- Table Component -->
      <MemoPembayaranApprovalTable
        :data="memoPembayarans"
        :loading="loading"
        :selected="selectedMemos"
        :pagination="pagination"
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
      @update:open="(v: boolean) => (showApprovalDialog = v)"
      @cancel="handleApprovalCancel"
      @confirm="handleApprovalConfirm"
    />

    <!-- Rejection Confirmation Dialog -->
    <RejectionConfirmationDialog
      :is-open="showRejectionDialog"
      :require-reason="true"
      @update:open="(v: boolean) => (showRejectionDialog = v)"
      @cancel="handleRejectionCancel"
      @confirm="handleRejectionConfirm"
    />

    <!-- Passcode Verification Dialog -->
    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="(v: boolean) => (showPasscodeDialog = v)"
      @cancel="handlePasscodeCancel"
      @verified="handlePasscodeVerified"
    />

    <!-- Success Dialog -->
    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="userName"
      document-type="Memo Pembayaran"
      @update:open="(v: boolean) => (showSuccessDialog = v)"
      @close="handleSuccessClose"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { CreditCard } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import MemoPembayaranApprovalFilter from "@/components/approval/MemoPembayaranApprovalFilter.vue";
import MemoPembayaranApprovalTable from "@/components/approval/MemoPembayaranApprovalTable.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import { useApi } from "@/composables/useApi";

// Props
const props = defineProps<{
  departments: Array<{ id: number; name: string }>;
  userRole: string;
}>();

// Reactive data
const memoPembayarans = ref<any[]>([]);
const loading = ref(false);
const selectedMemos = ref<number[]>([]);
const pagination = ref<any>(null);
const departments = ref(props.departments);
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

// API
const { get, post } = useApi();

// Computed
const breadcrumbs = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Memo Pembayaran", href: "/approval/memo-pembayarans" },
]);

const selectableStatuses = computed(() => {
  switch (userRole.value) {
    case "Kepala Toko":
      return ["In Progress"];
    case "Kabag":
      return ["In Progress"];
    case "Kadiv":
      return ["Verified", "In Progress"];
    case "Direksi":
      return ["Validated", "Verified"];
    case "Admin":
      return ["In Progress", "Verified", "Validated"];
    default:
      return [];
  }
});

// Methods
const fetchMemoPembayarans = async () => {
  loading.value = true;
  try {
    const queryParams = new URLSearchParams();
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) queryParams.append(key, value.toString());
    });

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

const handleSelect = (memoId: number, selected: boolean) => {
  if (selected) {
    selectedMemos.value.push(memoId);
  } else {
    selectedMemos.value = selectedMemos.value.filter((id) => id !== memoId);
  }
};

const handlePaginate = (page: number) => {
  filters.value.page = page;
  fetchMemoPembayarans();
};

const handleAction = async (actionData: any) => {
  const { action, id } = actionData;
  if (action === "reject") {
    pendingAction.value = { type: "single", action: "reject", ids: [id] };
    showRejectionDialog.value = true;
    return;
  }
  const mappedAction = action as "verify" | "validate" | "approve";
  pendingAction.value = { type: "single", action: mappedAction, ids: [id] };
  showApprovalDialog.value = true;
};

const handleBulkApprove = () => {
  if (selectedMemos.value.length === 0) return;

  // Determine action based on user role and selected rows' status
  const selectedRows = (memoPembayarans.value || []).filter((memo: any) =>
    selectedMemos.value.includes(memo.id)
  );
  const firstStatus: string | undefined = selectedRows[0]?.status;
  const role = userRole.value;

  let mappedAction: "verify" | "validate" | "approve" = "verify";

  // Map action based on user role + workflow
  if (role === "Kabag") {
    mappedAction = "verify"; // first step for Akunting flow
  } else if (role === "Kepala Toko") {
    mappedAction = "verify"; // first step for Toko flow
  } else if (role === "Kadiv") {
    mappedAction = "validate"; // DM/Zi&Glo (In Progress) or Toko (Verified)
  } else if (role === "Direksi") {
    mappedAction = "approve"; // final step for all flows
  } else if (role === "Admin") {
    // Admin can do any action based on status
    if (firstStatus === "In Progress") mappedAction = "verify";
    else if (firstStatus === "Verified") mappedAction = "validate";
    else if (firstStatus === "Validated") mappedAction = "approve";
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

const handleRejectionConfirm = (data: any) => {
  if (!pendingAction.value) return;
  const reason = typeof data === "string" ? data : data?.reason;
  pendingAction.value.reason = reason || "";
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

const isRowSelectableForRole = (memo: any) => {
  return selectableStatuses.value.includes(memo.status);
};

// Lifecycle
onMounted(() => {
  fetchMemoPembayarans();
  fetchDepartments();
  const page = usePage();
  const user = page.props.auth?.user as any;
  if (user) {
    userName.value = user.name || "User";
  }
});

// Watch for filter changes
watch(
  filters,
  () => {
    fetchMemoPembayarans();
  },
  { deep: true }
);
</script>
