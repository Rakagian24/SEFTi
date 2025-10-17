<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <svg
        class="w-5 h-5 text-gray-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <h3 class="text-lg font-semibold text-gray-900">Informasi Payment Voucher</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-4">
        <InfoItem icon="hash" label="No. Payment Voucher" :value="paymentVoucher.no_pv || '-'" mono />
        <InfoItem v-if="paymentVoucher.no_bk" icon="document" label="No. Bank Keluar" :value="paymentVoucher.no_bk" mono />
        <InfoItem icon="document" label="Tipe PV" :value="paymentVoucher.tipe_pv || '-'" />
        <InfoItem icon="building" label="Departemen" :value="paymentVoucher.department?.name || '-'" />
        <!-- <InfoItem icon="user" label="Supplier" :value="supplierName" /> -->
      </div>
      
      <div class="space-y-4">
        <InfoItem icon="calendar" label="Tanggal" :value="formatDate(paymentVoucher.tanggal)" />
        <InfoItem icon="credit-card" label="Metode Pembayaran" :value="paymentVoucher.metode_bayar || '-'" />
        <InfoItem icon="clipboard" label="Perihal" :value="paymentVoucher.perihal?.nama || '-'" />
        <!-- <InfoItem icon="currency" label="Nominal" :value="nominalDisplay" bold /> -->
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import InfoItem from "@/components/ui/InfoItem.vue";
import { formatCurrency } from "@/lib/currencyUtils";

const props = defineProps<{
  paymentVoucher: any;
}>();

const supplierName = computed(() => {
  if (props.paymentVoucher.tipe_pv === "Manual") {
    return props.paymentVoucher.manual_supplier || "-";
  }
  return props.paymentVoucher.supplier?.nama_supplier || "-";
});

const nominalDisplay = computed(() => {
  const nominal = formatCurrency(props.paymentVoucher.nominal || 0);
  const currency = props.paymentVoucher.currency ? ` (${props.paymentVoucher.currency})` : "";
  return nominal + currency;
});

function formatDate(date: string | null) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}
</script>
