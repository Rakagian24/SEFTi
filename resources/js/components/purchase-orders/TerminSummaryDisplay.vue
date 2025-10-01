<template>
  <div v-if="isLainnya && terminInfo && terminInfo.grand_total > 0" class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
    <h3 class="text-lg font-semibold text-gray-800 mb-3">Summary Pembayaran Termin</h3>

    <!-- Progress Bar -->
    <div class="mb-4">
      <div class="flex justify-between text-sm text-gray-600 mb-2">
        <span>Progress Termin</span>
        <span>{{ terminInfo.jumlah_termin_dibuat }} / {{ terminInfo.jumlah_termin }}</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-300"
          :class="{
            'bg-green-500': terminInfo.status_termin === 'completed',
            'bg-blue-500': terminInfo.status_termin === 'in_progress',
            'bg-gray-400': terminInfo.status_termin === 'not_started'
          }"
          :style="{ width: progressPercentage + '%' }"
        ></div>
      </div>
    </div>

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

    <!-- Status and Additional Info -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">Status Termin</div>
        <div class="flex items-center">
          <div
            class="w-3 h-3 rounded-full mr-2"
            :class="{
              'bg-green-500': terminInfo.status_termin === 'completed',
              'bg-yellow-500': terminInfo.status_termin === 'in_progress',
              'bg-gray-400': terminInfo.status_termin === 'not_started'
            }"
          ></div>
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
        </div>
      </div>

      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-600 mb-1">No. Referensi</div>
        <div class="font-medium text-gray-900">{{ terminInfo.no_referensi }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
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

const props = defineProps<{
  terminInfo: TerminInfo | null;
  isLainnya: boolean;
}>();

const progressPercentage = computed(() => {
  if (!props.terminInfo || props.terminInfo.jumlah_termin === 0) return 0;
  return Math.round((props.terminInfo.jumlah_termin_dibuat / props.terminInfo.jumlah_termin) * 100);
});
</script>
