<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              Detail Payment Voucher (Approval)
            </h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <CreditCard class="w-4 h-4 mr-1" />
              {{ paymentVoucher.no_pv }}
            </div>
            </div>
          </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span
            :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
              paymentVoucher.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(paymentVoucher.status)"
            ></div>
            {{ paymentVoucher.status }}
          </span>
        </div>
      </div>

      <!-- Rejection Reason Alert -->
      <div
        v-if="paymentVoucher.status === 'Rejected' && paymentVoucher.rejection_reason"
        class="mb-6"
      >
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg
                class="w-5 h-5 text-red-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Alasan Penolakan</h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{{ paymentVoucher.rejection_reason }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <BasicInfoCard :payment-voucher="paymentVoucher" />

          <!-- Supplier Detail for Manual -->
          <SupplierInfoCard
            v-if="paymentVoucher.tipe_pv === 'Manual' && paymentVoucher.metode_bayar === 'Transfer'"
            :payment-voucher="paymentVoucher"
          />

          <!-- Supplier & Bank Info from PO/Memo (Non-Manual) -->
          <SupplierBankInfoCard
            v-if="paymentVoucher.tipe_pv !== 'Manual' && paymentVoucher.metode_bayar === 'Transfer' && hasRelatedDocument"
            :payment-voucher="paymentVoucher"
          />

          <!-- Kredit (Credit Card) Details -->
          <CreditAccountInfoCard
            v-if="paymentVoucher.metode_bayar === 'Kartu Kredit'"
            :payment-voucher="paymentVoucher"
          />

          <!-- Giro Details -->
          <GiroInfoCard
            v-if="paymentVoucher.metode_bayar === 'Cek/Giro'"
            :payment-voucher="paymentVoucher"
          />

          <!-- Related Documents -->
          <RelatedDocumentCard v-if="hasRelatedDocument" :payment-voucher="paymentVoucher" />

          <!-- Documents Section -->
          <DocumentsCard :payment-voucher="paymentVoucher" />

          <!-- Additional Information -->
          <AdditionalInfoCard :payment-voucher="paymentVoucher" />
        </div>

        <div class="space-y-6">
          <ApprovalProgress
            :progress="approvalProgress"
            :purchase-order="paymentVoucher"
            :user-role="userRole"
            :can-verify="canVerify"
            :can-validate="canValidate"
            :can-approve="canApprove"
            :can-reject="canReject"
            @verify="handleVerify"
            @validate="handleValidate"
            @approve="handleApprove"
            @reject="handleRejectClick"
          />

          <SummaryCard :payment-voucher="paymentVoucher" />
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Kembali ke Approval Payment Voucher
        </button>
      </div>
    </div>

    <!-- Approval Confirmation Dialog -->
    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="(v: boolean) => (showApprovalDialog = v)"
      @cancel="
        () => {
          showApprovalDialog = false;
          pendingAction = null;
        }
      "
      @confirm="handleApprovalConfirm"
    />

    <!-- Rejection Confirmation Dialog -->
    <RejectionConfirmationDialog
      :is-open="showRejectionDialog"
      :require-reason="true"
      @update:open="(v: boolean) => (showRejectionDialog = v)"
      @cancel="
        () => {
          showRejectionDialog = false;
          pendingAction = null;
        }
      "
      @confirm="handleRejectionConfirm"
    />

    <!-- Passcode Verification Dialog -->
    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="(v: boolean) => (showPasscodeDialog = v)"
      @cancel="
        () => {
          showPasscodeDialog = false;
          pendingAction = null;
        }
      "
      @verified="handlePasscodeVerified"
    />

    <!-- Success Dialog -->
    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="user?.name || 'User'"
      document-type="Payment Voucher"
      @update:open="(v: boolean) => { showSuccessDialog = v; if (!v) { router.visit('/approval/payment-vouchers'); } }"
      @close="
        () => {
          showSuccessDialog = false;
          router.visit('/approval/payment-vouchers');
        }
      "
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { CreditCard } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ApprovalProgress from "@/components/approval/ApprovalProgress.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import BasicInfoCard from "@/components/payment-voucher/BasicInfoCard.vue";
import SupplierInfoCard from "@/components/payment-voucher/SupplierInfoCard.vue";
import SupplierBankInfoCard from "@/components/payment-voucher/SupplierBankInfoCard.vue";
import CreditAccountInfoCard from "@/components/payment-voucher/CreditAccountInfoCard.vue";
import GiroInfoCard from "@/components/payment-voucher/GiroInfoCard.vue";
import RelatedDocumentCard from "@/components/payment-voucher/RelatedDocumentCard.vue";
import DocumentsCard from "@/components/payment-voucher/DocumentsCard.vue";
import AdditionalInfoCard from "@/components/payment-voucher/AdditionalInfoCard.vue";
import SummaryCard from "@/components/payment-voucher/SummaryCard.vue";
import { useApi } from "@/composables/useApi";
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";
import AppLayout from "@/layouts/AppLayout.vue";

// Props
const props = defineProps<{
  paymentVoucher: any;
}>();

// Create a reactive reference to the payment voucher
const paymentVoucher = ref(props.paymentVoucher);

// Reactive data
const approvalProgress = ref<any[]>([]);
const loadingProgress = ref(false);
const userRole = ref("");
const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const successAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const pendingAction = ref<{
  type: "single";
  action: "verify" | "validate" | "approve" | "reject";
  ids: number[];
  singleItem?: any;
  reason?: string;
} | null>(null);
const { post, get } = useApi();

// Computed
const breadcrumbs = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Payment Voucher", href: "/approval/payment-vouchers" },
  { label: "Detail", href: "#" },
]);

const hasRelatedDocument = computed<boolean>(() => {
  return !!(paymentVoucher.value.purchase_order_id || paymentVoucher.value.memo_pembayaran_id);
});

defineOptions({ layout: AppLayout });

// Computed properties for approval permissions based on new workflow
const canVerify = computed(() => {
  const role = userRole.value;
  const status = paymentVoucher.value.status;

  // Admin can verify PV at In Progress
  if (role === "Admin") return status === "In Progress";

  // Payment Voucher workflow: Kabag/Kadiv verify (In Progress -> Verified)
  if (role === "Kabag" || role === "Kadiv") return status === "In Progress";

  return false;
});

const canValidate = computed(() => {
  const role = userRole.value;
  const status = paymentVoucher.value.status;
  const tipe = paymentVoucher.value.tipe_pv;

  if (role === "Admin") return status === "Verified" && (tipe === "Pajak");

  if (role === "Kadiv") return status === "Verified" && (tipe === "Pajak");

  return false;
});

const canApprove = computed(() => {
  const role = userRole.value;
  const status = paymentVoucher.value.status;
  const tipe = paymentVoucher.value.tipe_pv;

  // Admin can approve PV when status is Verified (general) or Validated (for Pajak)
  if (role === "Admin") {
    return status === "Verified" || (status === "Validated" && tipe === "Pajak");
  }

  // Direksi approves:
  // - default PV: after Verified
  // - PV Pajak: after Validated
  if (role === "Direksi") {
    if (status === "Verified" && tipe !== "Pajak") return true;
    if (status === "Validated" && tipe === "Pajak") return true;
    return false;
  }

  // Kabag/Kadiv should not see Approve button on PV detail
  return false;
});

const canReject = computed(() => {
  const status = paymentVoucher.value.status;
  const role = userRole.value;

  // Admin bypass: can reject any memo in progress
  if (role === "Admin") {
    return ["In Progress", "Verified", "Validated"].includes(status);
  }

  // Check if user has already performed any action
  const currentUser = page.props.auth?.user;
  if (!currentUser) return false;

  // Check if current user is the verifier, validator, or approver
  const hasPerformedAction =
    paymentVoucher.value.verifier_id === currentUser.id ||
    paymentVoucher.value.validator_id === currentUser.id ||
    paymentVoucher.value.approver_id === currentUser.id;

  if (hasPerformedAction) {
    return false; // User who already performed action cannot reject
  }

  // Semua role yang bisa approve juga bisa reject
  return (
    ["In Progress", "Verified", "Validated"].includes(status) &&
    (canVerify.value || canValidate.value || canApprove.value)
  );
});

// Methods
async function fetchApprovalProgress() {
  loadingProgress.value = true;
  try {
    const data = await get(
      `/api/approval/payment-vouchers/${props.paymentVoucher.id}/progress`
    );
    approvalProgress.value = data.progress || [];
  } catch (error) {
    console.error("Error fetching approval progress:", error);
  } finally {
    loadingProgress.value = false;
  }
}

function handleApprove() {
  const role = userRole.value;
  const creatorRole = paymentVoucher.value?.creator?.role?.name;
  const dept = paymentVoucher.value?.department?.name;
  const status = paymentVoucher.value.status;
  let mappedAction: "verify" | "validate" | "approve" = "approve";

  // Admin bypass logic - determine appropriate action based on current status and workflow
  if (role === "Admin") {
    if (status === "In Progress") {
      // Check if this should be verified first or approved directly
      if (
        creatorRole === "Staff Toko" &&
        dept !== "Zi&Glo" &&
        dept !== "Human Greatness"
      ) {
        mappedAction = "verify"; // Staff Toko needs verification first
      } else {
        mappedAction = "approve"; // Direct approval for others (DM, Akunting, Zi&Glo)
      }
    } else if (status === "Verified") {
      mappedAction = "approve"; // Always approve verified memos
    } else {
      mappedAction = "approve"; // fallback
    }
  }

  pendingAction.value = {
    type: "single",
    action: mappedAction,
    ids: [props.paymentVoucher.id],
    singleItem: props.paymentVoucher,
  };
  showApprovalDialog.value = true;
}

function handleRejectClick() {
  pendingAction.value = {
    type: "single",
    action: "reject",
    ids: [props.paymentVoucher.id],
    singleItem: props.paymentVoucher,
  };
  showRejectionDialog.value = true;
}

function handleVerify() {
  pendingAction.value = {
    type: "single",
    action: "verify",
    ids: [props.paymentVoucher.id],
    singleItem: props.paymentVoucher,
  };
  showApprovalDialog.value = true;
}

function handleValidate() {
  pendingAction.value = {
    type: "single",
    action: "validate",
    ids: [props.paymentVoucher.id],
    singleItem: props.paymentVoucher,
  };
  showApprovalDialog.value = true;
}

const handleApprovalConfirm = () => {
  if (!pendingAction.value) return;
  showApprovalDialog.value = false;
  passcodeAction.value = pendingAction.value.action;
  showPasscodeDialog.value = true;
};

const handleRejectionConfirm = (data: any) => {
  if (!pendingAction.value) return;
  const reason = typeof data === "string" ? data : data?.reason;
  pendingAction.value.reason = reason || "";
  showRejectionDialog.value = false;
  passcodeAction.value = "reject";
  showPasscodeDialog.value = true;
};

async function handlePasscodeVerified() {
  try {
    if (!pendingAction.value) return;

    const id = pendingAction.value.ids[0];
    if (pendingAction.value.action === "verify") {
      await post(`/api/approval/payment-vouchers/${id}/verify`);
      (props.paymentVoucher as any).status = "Verified";
    } else if (pendingAction.value.action === "validate") {
      await post(`/api/approval/payment-vouchers/${id}/validate`);
      (props.paymentVoucher as any).status = "Validated";
    } else if (pendingAction.value.action === "approve") {
      await post(`/api/approval/payment-vouchers/${id}/approve`);
      (props.paymentVoucher as any).status = "Approved";
    } else {
      await post(`/api/approval/payment-vouchers/${id}/reject`, {
        reason: pendingAction.value.reason || "",
      });
      (props.paymentVoucher as any).status = "Rejected";
    }

    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    // Show success dialog; redirect happens in SuccessDialog @close handler
    showSuccessDialog.value = true;
    return;
  } catch {
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
}


function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}

function goBack() {
  router.visit("/approval/payment-vouchers");
}

// Initialize user role and fetch progress
const page = usePage();
const user = page.props.auth?.user;
if (user && (user as any).role) {
  userRole.value = (user as any).role.name || "";
}

// Lifecycle
onMounted(() => {
  fetchApprovalProgress();

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

<style scoped>
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.hover\:bg-white\/50:hover {
  background-color: rgba(255, 255, 255, 0.5);
}
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill,
    stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
@media (max-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
.bg-yellow-100 {
  background-color: #fef3c7;
}
.text-yellow-800 {
  color: #92400e;
}
.bg-green-100 {
  background-color: #dcfce7;
}
.text-green-800 {
  color: #166534;
}
.bg-red-100 {
  background-color: #fee2e2;
}
.text-red-800 {
  color: #991b1b;
}
.bg-gray-100 {
  background-color: #f3f4f6;
}
.text-gray-800 {
  color: #1f2937;
}
.text-green-600 {
  color: #059669;
}
.text-blue-600 {
  color: #2563eb;
}
.text-red-600 {
  color: #dc2626;
}
.bg-blue-100 {
  background-color: #dbeafe;
}
.text-blue-800 {
  color: #1e40af;
}
.bg-purple-100 {
  background-color: #e9d5ff;
}
.text-purple-800 {
  color: #6b21a8;
}
</style>
