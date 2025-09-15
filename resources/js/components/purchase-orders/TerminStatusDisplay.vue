<template>
  <div v-if="terminInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
    <div class="flex items-center justify-between mb-2">
      <span class="font-medium text-blue-800">Informasi Termin</span>
      <span
        :class="{
          'px-2 py-1 rounded-full text-xs font-medium': true,
          'bg-green-100 text-green-800': terminInfo.status_termin === 'completed',
          'bg-yellow-100 text-yellow-800': terminInfo.status_termin === 'in_progress',
          'bg-gray-100 text-gray-800': terminInfo.status_termin === 'not_started',
        }"
      >
        {{
          terminInfo.status_termin === "completed"
            ? "Selesai"
            : terminInfo.status_termin === "in_progress"
            ? "Sedang Berjalan"
            : "Belum Dimulai"
        }}
      </span>
    </div>
    <div class="grid grid-cols-2 gap-2 text-blue-700">
      <div>
        <span class="font-medium">Termin Dibuat:</span>
        <span class="ml-1"
          >{{ terminInfo.jumlah_termin_dibuat }} dari {{ terminInfo.jumlah_termin }}</span
        >
      </div>
      <div>
        <span class="font-medium">Total Cicilan:</span>
        <span class="ml-1">{{ formatCurrency(terminInfo.total_cicilan) }}</span>
      </div>
      <div v-if="terminInfo.grand_total > 0" class="col-span-2">
        <span class="font-medium">Grand Total:</span>
        <span class="ml-1">{{ formatCurrency(terminInfo.grand_total) }}</span>
      </div>
      <div v-if="terminInfo.sisa_pembayaran > 0" class="col-span-2">
        <span class="font-medium">Sisa Pembayaran:</span>
        <span class="ml-1 text-orange-600 font-semibold">{{
          formatCurrency(terminInfo.sisa_pembayaran)
        }}</span>
      </div>
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
  status_termin: "completed" | "in_progress" | "not_started";
  barang_list?: any[];
  grand_total: number;
}

defineProps<{
  terminInfo: TerminInfo | null;
}>();
</script>
