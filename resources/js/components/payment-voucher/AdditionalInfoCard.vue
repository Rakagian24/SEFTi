<template>
  <div
    v-if="hasAdditionalInfo"
    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
  >
    <div class="flex items-center gap-2 mb-4">
      <FileText class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
    </div>

    <div class="space-y-6">
      <div v-if="paymentVoucher.note">
        <p class="text-sm font-medium text-gray-900 mb-2">Catatan</p>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
            {{ paymentVoucher.note }}
          </p>
        </div>
      </div>

      <div v-if="paymentVoucher.keterangan">
        <p class="text-sm font-medium text-gray-900 mb-2">Keterangan</p>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
            {{ paymentVoucher.keterangan }}
          </p>
        </div>
      </div>

      <div v-if="paymentVoucher.verification_notes">
        <p class="text-sm font-medium text-gray-900 mb-2">Catatan Verifikasi</p>
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
            {{ paymentVoucher.verification_notes }}
          </p>
        </div>
      </div>

      <div v-if="paymentVoucher.approval_notes">
        <p class="text-sm font-medium text-gray-900 mb-2">Catatan Approval</p>
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
            {{ paymentVoucher.approval_notes }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FileText } from "lucide-vue-next";

const props = defineProps<{
  paymentVoucher: any;
}>();

const hasAdditionalInfo = computed(() => {
  return (
    props.paymentVoucher.note ||
    props.paymentVoucher.keterangan ||
    props.paymentVoucher.verification_notes ||
    props.paymentVoucher.approval_notes
  );
});
</script>
