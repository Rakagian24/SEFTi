<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <User class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Informasi Supplier</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-4">
        <InfoItem icon="user" label="Nama Supplier" :value="paymentVoucher.manual_supplier || '-'" />
        <InfoItem icon="phone" label="No. Telepon" :value="paymentVoucher.manual_no_telepon || '-'" />
        <InfoItem icon="map" label="Alamat" :value="paymentVoucher.manual_alamat || '-'" />
      </div>

      <div class="space-y-4">
        <InfoItem icon="building" label="Bank" :value="paymentVoucher.manual_nama_bank || '-'" />
        <InfoItem icon="user" label="Nama Pemilik Rekening" :value="paymentVoucher.manual_nama_pemilik_rekening || '-'" />
        <InfoItem icon="credit-card" label="No. Rekening" :value="paymentVoucher.manual_no_rekening || '-'" mono />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { User } from "lucide-vue-next";
import InfoItem from "@/components/ui/InfoItem.vue";

defineProps<{
  paymentVoucher: any;
}>();
</script>
