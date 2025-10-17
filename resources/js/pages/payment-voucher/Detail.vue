<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Detail Payment Voucher</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            {{ paymentVoucher.no_pv || "Detail dokumen Payment Voucher" }}
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span
            :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusClass(
              paymentVoucher.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(paymentVoucher.status)"
            ></div>
            {{ paymentVoucher.status }}
          </span>

          <!-- Download Button -->
          <button
            v-if="['In Progress', 'Approved'].includes(paymentVoucher.status)"
            @click="downloadDocument"
            class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <Download class="w-4 h-4" />
            Download PDF
          </button>

          <!-- Log Activity Button -->
          <button
            @click="viewLog"
            class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <History class="w-4 h-4" />
            Log Activity
          </button>
        </div>
      </div>

      <!-- Rejection Reason Banner -->
      <div
        v-if="paymentVoucher.status === 'Rejected' && paymentVoucher.rejection_reason"
        class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800"
      >
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="text-sm font-semibold">Alasan Ditolak</p>
            <p class="text-sm mt-1 whitespace-pre-wrap">{{ paymentVoucher.rejection_reason }}</p>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info (2 columns width) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Document Details Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Dokumen</h3>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
              <InfoItem icon="calendar" label="Tanggal" :value="formatDate(paymentVoucher.tanggal)" />
              <InfoItem icon="file" label="No. PV" :value="paymentVoucher.no_pv" mono />
              <InfoItem icon="tag" label="Tipe" :value="paymentVoucher.tipe_pv" />
              <InfoItem icon="building" label="Departemen" :value="paymentVoucher.department?.name" />
              <InfoItem 
                icon="link" 
                label="Sumber" 
                :value="getSourceLabel()" 
              />
              <InfoItem 
                icon="document" 
                label="Perihal" 
                :value="paymentVoucher.purchase_order?.perihal?.nama || paymentVoucher.memo_pembayaran?.perihal" 
              />
            </div>
          </div>

          <!-- Payment Method Details Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Detail Pembayaran</h3>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
              <InfoItem icon="credit-card" label="Metode Bayar" :value="paymentVoucher.metode_bayar" />
              <InfoItem icon="currency" label="Currency" :value="paymentVoucher.currency || 'IDR'" />
              <InfoItem icon="money" label="Nominal" :value="formatCurrency(paymentVoucher.nominal || paymentVoucher.purchase_order?.total || 0)" />

              <!-- Transfer Details -->
              <template v-if="paymentVoucher.metode_bayar === 'Transfer'">
                <InfoItem icon="user" label="Supplier" :value="paymentVoucher.supplier?.nama_supplier" />
                <InfoItem icon="bank" label="Bank" :value="getBankName()" />
                <InfoItem icon="user" label="Nama Rekening" :value="getAccountName()" />
                <InfoItem icon="hash" label="No. Rekening" :value="getAccountNumber()" mono class="col-span-2" />
              </template>

              <!-- Credit Card Details -->
              <template v-else-if="paymentVoucher.metode_bayar === 'Kartu Kredit'">
                <InfoItem icon="credit-card" label="No. Kartu" :value="getCreditCardNumber()" mono />
                <InfoItem icon="user" label="Nama Pemilik" :value="getCreditCardOwner()" />
                <InfoItem icon="bank" label="Bank" :value="getCreditCardBank()" />
              </template>

              <!-- Cek/Giro Details -->
              <template v-else-if="paymentVoucher.metode_bayar === 'Cek/Giro'">
                <InfoItem icon="hash" label="No. Giro" :value="getGiroNumber()" mono />
                <InfoItem icon="calendar" label="Tanggal Giro" :value="formatDate(getGiroDate())" />
                <InfoItem icon="calendar" label="Tanggal Cair" :value="formatDate(getGiroClearDate())" />
              </template>
            </div>
          </div>

          <!-- Approval & Documents Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Approval & Dokumen</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="space-y-3">
                <ApprovalItem label="Verified by" :name="paymentVoucher.verifier?.name" :date="paymentVoucher.verified_at" />
                <ApprovalItem label="Approved by" :name="paymentVoucher.approver?.name" :date="paymentVoucher.approved_at" />
              </div>
              <div class="space-y-3">
                <div v-if="paymentVoucher.verification_notes" class="text-sm">
                  <span class="font-medium text-gray-900">Catatan Verifikasi:</span>
                  <p class="text-gray-600 mt-1">{{ paymentVoucher.verification_notes }}</p>
                </div>
                <div v-if="paymentVoucher.approval_notes" class="text-sm">
                  <span class="font-medium text-gray-900">Catatan Approval:</span>
                  <p class="text-gray-600 mt-1">{{ paymentVoucher.approval_notes }}</p>
                </div>
              </div>
            </div>

            <!-- Documents -->
            <div class="border-t pt-4">
              <p class="text-sm font-medium text-gray-900 mb-3">Dokumen Terlampir</p>
              <div v-if="(paymentVoucher.documents || []).length" class="space-y-2">
                <div v-for="doc in paymentVoucher.documents" :key="doc.id" class="flex items-center justify-between text-sm border border-gray-200 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium">{{ doc.type }}</span>
                    <span class="text-gray-500">{{ doc.original_name || 'unnamed.pdf' }}</span>
                    <span v-if="doc.active === false" class="text-xs px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full">inactive</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500 italic">Tidak ada dokumen terlampir.</div>
            </div>
          </div>

          <!-- Notes (if any) -->
          <div v-if="paymentVoucher.keterangan" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Keterangan</h3>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">{{ paymentVoucher.keterangan }}</p>
            </div>
          </div>
        </div>

        <!-- Right Column - Summary -->
        <div class="space-y-6">
          <!-- Payment Summary Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
            </div>

            <div class="space-y-3">
              <SummaryItem label="Total" :value="getTotal()" />
              <SummaryItem label="Diskon" :value="getDiskon()" negative />
              <SummaryItem label="PPN (11%)" :value="getPPN()" />
              <SummaryItem label="PPH" :value="getPPH()" :suffix="getPPHLabel()" />

              <div class="border-t border-gray-200 pt-3 mt-3">
                <div class="flex items-center justify-between mb-4">
                  <span class="text-base font-semibold text-gray-900">Grand Total</span>
                  <span class="text-base font-bold text-green-600">{{ formatCurrency(getGrandTotal()) }}</span>
                </div>

                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-4 text-center">
                  <p class="text-xs text-gray-600 mb-1">Total Pembayaran</p>
                  <p class="text-2xl font-bold text-indigo-600">{{ formatCurrency(getGrandTotal()) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <ArrowLeft class="w-4 h-4" />
          Kembali ke Payment Voucher
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards, Download, History, ArrowLeft } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";

// InfoItem Component
const InfoItem = (props: { icon: string; label: string; value: any; mono?: boolean; class?: string }) => null;

// ApprovalItem Component
const ApprovalItem = (props: { label: string; name?: string; date?: string }) => null;

// SummaryItem Component
const SummaryItem = (props: { label: string; value: any; negative?: boolean; suffix?: string }) => null;

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-voucher" },
  { label: "Detail" },
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  paymentVoucher: any;
}>();

const paymentVoucher = computed(() => props.paymentVoucher || {});

// Helper functions
function getSourceLabel() {
  const pv = paymentVoucher.value;
  if (pv.tipe_pv === 'Lainnya') {
    return `MB: ${pv.memo_pembayaran?.no_memo || '-'}`;
  } else if (pv.tipe_pv === 'Manual') {
    return 'Manual';
  } else {
    return `PO: ${pv.purchase_order?.no_po || pv.purchaseOrder?.no_po || '-'}`;
  }
}

function getBankName() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.bank_supplier_account?.bank?.nama_bank || 
         pv.purchaseOrder?.bankSupplierAccount?.bank?.nama_bank || '-';
}

function getAccountName() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.bank_supplier_account?.nama_rekening || 
         pv.purchaseOrder?.bankSupplierAccount?.nama_rekening || '-';
}

function getAccountNumber() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.bank_supplier_account?.no_rekening || 
         pv.purchaseOrder?.bankSupplierAccount?.no_rekening || '-';
}

function getCreditCardNumber() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.credit_card?.no_kartu_kredit || 
         pv.purchaseOrder?.creditCard?.no_kartu_kredit || '-';
}

function getCreditCardOwner() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.credit_card?.nama_pemilik || 
         pv.purchaseOrder?.creditCard?.nama_pemilik || '-';
}

function getCreditCardBank() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.credit_card?.bank?.nama_bank || 
         pv.purchaseOrder?.creditCard?.bank?.nama_bank || '-';
}

function getGiroNumber() {
  const pv = paymentVoucher.value;
  return pv.no_giro || pv.purchase_order?.no_giro || pv.purchaseOrder?.no_giro || '-';
}

function getGiroDate() {
  const pv = paymentVoucher.value;
  return pv.tanggal_giro || pv.purchase_order?.tanggal_giro || pv.purchaseOrder?.tanggal_giro;
}

function getGiroClearDate() {
  const pv = paymentVoucher.value;
  return pv.tanggal_cair || pv.purchase_order?.tanggal_cair || pv.purchaseOrder?.tanggal_cair;
}

function getTotal() {
  const pv = paymentVoucher.value;
  return pv.purchase_order?.total || pv.nominal || 0;
}

function getDiskon() {
  return paymentVoucher.value.diskon || 0;
}

function getPPN() {
  return paymentVoucher.value.ppn_nominal || 0;
}

function getPPH() {
  return paymentVoucher.value.pph_nominal || 0;
}

function getPPHLabel() {
  const pph = paymentVoucher.value.purchase_order?.pph?.nama_pph || 
              paymentVoucher.value.purchaseOrder?.pph?.nama_pph;
  return pph ? `(${pph})` : '';
}

function getGrandTotal() {
  const pv = paymentVoucher.value;
  return pv.grand_total || pv.purchase_order?.total || pv.nominal || 0;
}

function goBack() {
  router.visit("/payment-voucher");
}

function downloadDocument() {
  window.open(`/payment-voucher/${paymentVoucher.value.id}/download`, "_blank");
}

function viewLog() {
  router.visit(`/payment-voucher/${paymentVoucher.value.id}/log`);
}

function formatDate(date: string) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

function getStatusClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}
</script>

<script lang="ts">
// Define reusable components
export const InfoItem = {
  props: {
    icon: String,
    label: String,
    value: [String, Number],
    mono: Boolean,
    class: String
  },
  template: `
    <div :class="class">
      <p class="text-xs font-medium text-gray-500 mb-1">{{ label }}</p>
      <p :class="['text-sm text-gray-900', { 'font-mono': mono }]">{{ value || '-' }}</p>
    </div>
  `
};

export const ApprovalItem = {
  props: {
    label: String,
    name: String,
    date: String
  },
  setup(props) {
    const formatDate = (date: string) => {
      if (!date) return "-";
      return new Date(date).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    
    return { formatDate };
  },
  template: `
    <div class="flex items-start justify-between">
      <span class="text-sm text-gray-600">{{ label }}</span>
      <div class="text-right">
        <p class="text-sm font-medium text-gray-900">{{ name || '-' }}</p>
        <p class="text-xs text-gray-500">{{ formatDate(date) }}</p>
      </div>
    </div>
  `
};

export const SummaryItem = {
  props: {
    label: String,
    value: [String, Number],
    negative: Boolean,
    suffix: String
  },
  setup() {
    const { formatCurrency } = { formatCurrency };
    return { formatCurrency };
  },
  template: `
    <div class="flex items-center justify-between">
      <span class="text-sm text-gray-600">{{ label }} {{ suffix }}</span>
      <span :class="['text-sm font-medium', negative ? 'text-red-600' : 'text-gray-900']">
        {{ negative ? '-' : '' }}{{ formatCurrency(value) }}
      </span>
    </div>
  `
};
</script>