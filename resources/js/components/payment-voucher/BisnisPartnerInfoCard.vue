<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <Building2 class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Informasi Bisnis Partner</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Bisnis Partner Info -->
      <div class="space-y-4">
        <div class="pb-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bisnis Partner</p>
        </div>
        <InfoItem icon="user" label="Nama Bisnis Partner" :value="bpInfo.name" />
        <InfoItem icon="map" label="Alamat" :value="bpInfo.address" />
        <InfoItem icon="phone" label="No. Telepon" :value="bpInfo.phone" />
      </div>

      <!-- Bank Info -->
      <div class="space-y-4">
        <div class="pb-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rekening Bank</p>
        </div>
        <InfoItem icon="building" label="Nama Bank" :value="bankInfo.bankName" />
        <InfoItem icon="user" label="Nama Rekening" :value="bankInfo.accountName" />
        <InfoItem icon="credit-card" label="No. Rekening/VA" :value="bankInfo.accountNumber" mono />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Building2 } from "lucide-vue-next";
import InfoItem from "@/components/ui/InfoItem.vue";

const props = defineProps<{
  paymentVoucher: any;
}>();

const bpInfo = computed(() => {
  const pv = props.paymentVoucher || {};
  const poa = pv.poAnggaran || pv.po_anggaran || null;
  const bp = pv.bisnisPartner || pv.bisnis_partner || poa?.bisnisPartner || poa?.bisnis_partner || null;

  return {
    name: bp?.nama_bp || "-",
    address: bp?.alamat || "-",
    phone: bp?.no_telepon || "-",
  };
});

const bankInfo = computed(() => {
  const pv = props.paymentVoucher || {};
  const poa = pv.poAnggaran || pv.po_anggaran || null;
  const bp = pv.bisnisPartner || pv.bisnis_partner || poa?.bisnisPartner || poa?.bisnis_partner || null;

  const bankFromBp = bp?.bank || null;
  const bankFromPoa = poa?.bank || null;

  return {
    // Prefer Bisnis Partner bank relation, then Bisnis Partner nama_bank accessor, then Po Anggaran bank
    bankName: bankFromBp?.nama_bank || bp?.nama_bank || bankFromPoa?.nama_bank || "-",
    accountName: bp?.nama_rekening || poa?.nama_rekening || "-",
    accountNumber: bp?.no_rekening_va || poa?.no_rekening || "-",
  };
});
</script>
