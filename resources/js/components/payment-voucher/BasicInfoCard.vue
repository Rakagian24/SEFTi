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

        <template v-if="poAnggaran">
          <div class="pt-2 border-t border-gray-100">
            <InfoItem icon="file-text" label="PO Anggaran" :value="poAnggaran.no_po_anggaran || '-'" mono />
            <InfoItem icon="user" label="Bisnis Partner" :value="bisnisPartnerName" />
            <InfoItem icon="map" label="Alamat Bisnis Partner" :value="bisnisPartnerAddress" />
          </div>
        </template>
      </div>

      <div class="space-y-4">
        <InfoItem icon="calendar" label="Tanggal" :value="formatDate(paymentVoucher.tanggal)" />
        <InfoItem icon="credit-card" label="Metode Pembayaran" :value="(paymentVoucher.metode_bayar || paymentVoucher.purchaseOrder?.metode_pembayaran || paymentVoucher.purchase_order?.metode_pembayaran || paymentVoucher.memoPembayaran?.metode_pembayaran || paymentVoucher.memo_pembayaran?.metode_pembayaran || '-')" />

        <InfoItem icon="clipboard" label="Perihal" :value="paymentVoucher.perihal?.nama || '-'" />
        <template v-if="poAnggaran">
          <InfoItem icon="building" label="Departemen PO Anggaran" :value="poAnggaran.department?.name || '-'"></InfoItem>
          <InfoItem icon="calendar" label="Tanggal PO Anggaran" :value="formatDate(poAnggaran.tanggal)"></InfoItem>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import InfoItem from "@/components/ui/InfoItem.vue";

const props = defineProps<{
  paymentVoucher: any;
}>();

const poAnggaran = computed(() => props.paymentVoucher.poAnggaran || props.paymentVoucher.po_anggaran || null);

const bisnisPartnerName = computed(() => {
  if (!poAnggaran.value?.bisnisPartner) return poAnggaran.value?.bisnis_partner?.nama_bp || "-";
  return poAnggaran.value.bisnisPartner?.nama_bp || poAnggaran.value.bisnisPartner?.name || "-";
});

const bisnisPartnerAddress = computed(() => {
  return (
    poAnggaran.value?.bisnisPartner?.alamat ||
    poAnggaran.value?.bisnis_partner?.alamat ||
    "-"
  );
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
