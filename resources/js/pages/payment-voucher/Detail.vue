<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6md:px-6 md:pt-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="mt-2 mb-4 flex items-start justify-between gap-3 md:mt-4 md:mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">
              Detail Payment Voucher
            </h1>
            <div class="mt-2 flex items-center text-xs text-gray-500 md:text-sm">
              <TicketPercent class="mr-1 h-4 w-4" />
              {{ paymentVoucher.no_pv || "Draft" }}
            </div>
          </div>
        </div>

        <div class="flex flex-col items-end gap-2">
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

          <div class="hidden md:flex items-center gap-3">
            <button
              v-if="canEdit"
              @click="goToEdit"
              class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              {{ paymentVoucher.status === "Rejected" ? "Perbaiki" : "Edit" }}
            </button>

            <button
              v-if="
                ['In Progress', 'Verified', 'Validated', 'Approved'].includes(
                  paymentVoucher.status
                )
              "
              @click="downloadPV"
              class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              Download PDF
            </button>

            <button
              @click="goToLog"
              class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 17v-6h13M9 7h13M4 7h.01M4 17h.01"
                />
              </svg>
              Log
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile actions: Download & Log -->
      <div class="mb-4 flex items-center gap-2 md:mb-6 md:hidden">
        <button
          type="button"
          class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm active:bg-gray-50"
          @click="downloadPV"
          v-if="['In Progress', 'Verified', 'Validated', 'Approved'].includes(paymentVoucher.status)"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"
            />
          </svg>
          <span>Download</span>
        </button>

        <button
          type="button"
          class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm active:bg-gray-50"
          @click="goToLog"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-5m-3-4h3m-3 4h3"
            />
          </svg>
          <span>Log</span>
        </button>
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
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <BasicInfoCard :payment-voucher="paymentVoucher" />

          <!-- Supplier Detail for Manual -->
          <SupplierInfoCard
                v-if="paymentVoucher.tipe_pv === 'Manual' || paymentVoucher.tipe_pv === 'Pajak'"
                :payment-voucher="paymentVoucher"
            />

          <!-- Supplier & Bank Info from PO/Memo (Non-Manual) -->
          <SupplierBankInfoCard
            v-if="paymentVoucher.tipe_pv !== 'Manual' && paymentVoucher.tipe_pv !== 'Pajak' && paymentVoucher.tipe_pv !== 'Anggaran' && hasRelatedDocument"
            :payment-voucher="paymentVoucher"
          />

          <!-- Bisnis Partner Info for Anggaran -->
          <BisnisPartnerInfoCard
            v-if="paymentVoucher.tipe_pv === 'Anggaran'"
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
          <!-- Approval Progress (desktop / tablet only) -->
          <div class="hidden md:block">
            <ApprovalProgress
              :progress="approvalProgress"
              :purchase-order="paymentVoucher"
              :user-role="userRole"
            />
          </div>

          <!-- Kelengkapan Dokumen Info -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700">Kelengkapan Dokumen</span>
              <span
                class="text-xs font-semibold px-2.5 py-1 rounded-full"
                :class="paymentVoucher.kelengkapan_dokumen ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ paymentVoucher.kelengkapan_dokumen ? 'Lengkap' : 'Tidak Lengkap' }}
              </span>
            </div>
          </div>

          <SummaryCard :payment-voucher="paymentVoucher" />
        </div>
      </div>

      <!-- Mobile-only Approval Progress at bottom -->
      <div class="mt-6 md:hidden">
        <ApprovalProgress
          :progress="approvalProgress"
          :purchase-order="paymentVoucher"
          :user-role="userRole"
        />
      </div>

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
          Kembali ke Daftar Payment Voucher
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { TicketPercent } from "lucide-vue-next";
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";
import ApprovalProgress from "@/components/approval/ApprovalProgress.vue";
import BasicInfoCard from "@/components/payment-voucher/BasicInfoCard.vue";
import SupplierInfoCard from "@/components/payment-voucher/SupplierInfoCard.vue";
import SupplierBankInfoCard from "@/components/payment-voucher/SupplierBankInfoCard.vue";
import BisnisPartnerInfoCard from "@/components/payment-voucher/BisnisPartnerInfoCard.vue";
import CreditAccountInfoCard from "@/components/payment-voucher/CreditAccountInfoCard.vue";
import GiroInfoCard from "@/components/payment-voucher/GiroInfoCard.vue";
import RelatedDocumentCard from "@/components/payment-voucher/RelatedDocumentCard.vue";
import DocumentsCard from "@/components/payment-voucher/DocumentsCard.vue";
import AdditionalInfoCard from "@/components/payment-voucher/AdditionalInfoCard.vue";
import SummaryCard from "@/components/payment-voucher/SummaryCard.vue";
import { useApi } from "@/composables/useApi";

const props = defineProps<{ paymentVoucher: any }>();
const paymentVoucher = ref(props.paymentVoucher);
const { get } = useApi();

const approvalProgress = ref<any[]>([]);
const loadingProgress = ref(false);
const userRole = ref("");
const page = usePage();
const user = page.props.auth?.user;
if (user && (user as any).role) {
  userRole.value = (user as any).role.name || "";
}

const isCreator = computed<boolean>(() => {
  const currentUserId = (page.props.auth?.user as any)?.id;
  const creatorId = (paymentVoucher.value as any)?.creator?.id;
  return Boolean(
    currentUserId && creatorId && String(currentUserId) === String(creatorId)
  );
});

const isAdmin = computed<boolean>(() => {
  const userRole = (page.props.auth?.user as any)?.role?.name;
  return userRole === "Admin";
});

const canEdit = computed<boolean>(() => {
  if (paymentVoucher.value.status === "Draft") {
    return isCreator.value;
  }
  if (paymentVoucher.value.status === "Rejected") {
    return isCreator.value || isAdmin.value;
  }
  return false;
});

const hasRelatedDocument = computed<boolean>(() => {
  const pv: any = paymentVoucher.value || {};
  return !!(pv.purchase_order_id || pv.memo_pembayaran_id || pv.po_anggaran_id || pv.poAnggaran || pv.po_anggaran);
});

// Normalize metode pembayaran for conditional rendering
// const metodePembayaran = computed<string | null>(() => {
//   const pv: any = paymentVoucher.value || {};
//   return (
//     pv.metode_bayar ||
//     pv.purchaseOrder?.metode_pembayaran ||
//     pv.purchase_order?.metode_pembayaran ||
//     pv.memoPembayaran?.metode_pembayaran ||
//     pv.memo_pembayaran?.metode_pembayaran ||
//     null
//   );
// });

async function fetchApprovalProgress() {
  loadingProgress.value = true;
  try {
    const response = await get(
      `/api/approval/payment-vouchers/${paymentVoucher.value.id}/progress`
    );
    approvalProgress.value = response.progress || [];
  } catch (error) {
    console.error("Error fetching approval progress:", error);
  } finally {
    loadingProgress.value = false;
  }
}

onMounted(async () => {
  await fetchApprovalProgress();
  try {
    const pv: any = paymentVoucher.value || {};
    const po: any = pv.purchaseOrder || pv.purchase_order || null;
    const memo: any = pv.memoPembayaran || pv.memo_pembayaran || null;
    const bankSupplierAccount =
      pv.bankSupplierAccount ||
      pv.bank_supplier_account ||
      po?.bankSupplierAccount ||
      po?.bank_supplier_account ||
      memo?.bankSupplierAccount ||
      memo?.bank_supplier_account ||
      null;
    console.group("[PaymentVoucher Detail] Data Snapshot");
    console.log({ pv, purchaseOrder: po, memoPembayaran: memo, bankSupplierAccount });
    console.groupEnd();
  } catch (e) {
    console.error("[PaymentVoucher Detail] Logging error", e);
  }
});

const breadcrumbs = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-voucher" },
  { label: "Detail", href: "#" },
]);

defineOptions({ layout: AppLayout });

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}

function goBack() {
  router.visit("/payment-voucher");
}

function goToEdit() {
  router.visit(`/payment-voucher/${paymentVoucher.value.id}/edit`);
}

function goToLog() {
  router.visit(`/payment-voucher/${paymentVoucher.value.id}/log`);
}

function downloadPV() {
  window.open(`/payment-voucher/${paymentVoucher.value.id}/download`, "_blank");
}
</script>
