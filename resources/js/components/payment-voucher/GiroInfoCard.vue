<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <FileText class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Informasi Cek/Giro</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <InfoItem icon="document" label="No. Cek/Giro" :value="paymentVoucher.no_giro || '-'" mono />
      <InfoItem icon="calendar" label="Tanggal Giro" :value="formatDate(paymentVoucher.tanggal_giro)" />
      <InfoItem icon="calendar" label="Tanggal Cair" :value="formatDate(paymentVoucher.tanggal_cair)" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { FileText } from "lucide-vue-next";
import InfoItem from "@/components/ui/InfoItem.vue";

defineProps<{
  paymentVoucher: any;
}>();

function formatDate(date: string | null) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}
</script>
