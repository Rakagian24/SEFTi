<template>
  <div v-if="isLainnya && terminInfo && terminInfo.grand_total > 0" class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
    <h3 class="text-lg font-semibold text-gray-800 mb-3">Summary Pembayaran Termin</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">Grand Total</div>
        <div class="text-xl font-bold text-gray-900">{{ formatCurrency(terminInfo.grand_total) }}</div>
      </div>
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">Total Cicilan</div>
        <div class="text-xl font-bold text-blue-600">{{ formatCurrency(terminInfo.total_cicilan) }}</div>
      </div>
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">Sisa Pembayaran</div>
        <div class="text-xl font-bold text-orange-600">{{ formatCurrency(terminInfo.sisa_pembayaran) }}</div>
      </div>
    </div>
    <div class="mt-3 text-sm text-gray-600">
      <span class="font-medium">Status:</span>
      <span
        :class="{
          'text-green-600 font-semibold': terminInfo.status_termin === 'completed',
          'text-yellow-600 font-semibold': terminInfo.status_termin === 'in_progress',
          'text-gray-600 font-semibold': terminInfo.status_termin === 'not_started'
        }"
      >
        {{
          terminInfo.status_termin === 'completed' ? 'Termin Selesai' :
          terminInfo.status_termin === 'in_progress' ? 'Termin Sedang Berjalan' : 'Termin Belum Dimulai'
        }}
      </span>
      <span class="ml-2">
        ({{ terminInfo.jumlah_termin_dibuat }} dari {{ terminInfo.jumlah_termin }} termin)
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatCurrency } from "@/lib/currencyUtils";

interface TerminInfo {
  id: number;
  no_referensi: string;
  jumlah_termin: number;
  total_cicilan: number;
  sisa_pembayaran: number;
  jumlah_termin_dibuat: number;
  status_termin: 'completed' | 'in_progress' | 'not_started';
  barang_list?: any[];
  grand_total: number;
}

defineProps<{
  terminInfo: TerminInfo | null;
  isLainnya: boolean;
}>();
</script>
