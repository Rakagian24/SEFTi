<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <Paperclip class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Dokumen Pendukung</h3>
    </div>

    <div v-if="documents.length > 0" class="space-y-3">
      <div
        v-for="doc in documents"
        :key="doc.id"
        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
      >
        <div class="flex items-center gap-3">
          <FileText class="w-5 h-5 text-blue-600" />
          <div>
            <p class="text-sm font-medium text-gray-900">{{ getDocumentLabel(doc.type) }}</p>
            <p class="text-xs text-gray-500">{{ doc.original_name || "Document" }}</p>
          </div>
        </div>
        <a
          :href="`/payment-voucher/documents/${doc.id}/download`"
          target="_blank"
          class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
        >
          <Download class="w-3 h-3" />
          Download
        </a>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500">
      <Paperclip class="w-12 h-12 mx-auto text-gray-400 mb-2" />
      <p>Tidak ada dokumen pendukung</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Paperclip, FileText, Download } from "lucide-vue-next";

const props = defineProps<{
  paymentVoucher: any;
}>();

const documents = computed(() => {
  return props.paymentVoucher.documents?.filter((doc: any) => doc.active && doc.path) || [];
});

function getDocumentLabel(type: string): string {
  const labels: Record<string, string> = {
    bukti_input_bca: "Bukti Input BCA",
    bukti_transfer_bca: "Bukti Transfer BCA",
    invoice: "Invoice",
    surat_jalan: "Surat Jalan",
    efaktur: "E-Faktur",
    lainnya: "Lainnya",
  };
  return labels[type] || type;
}
</script>
