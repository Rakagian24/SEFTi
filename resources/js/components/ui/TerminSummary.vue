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
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <h3 class="text-lg font-semibold text-gray-900">Ringkasan Termin</h3>
    </div>

    <!-- Progress Termin -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Progress Termin</span>
          <div
            class="w-2 h-2 rounded-full"
            :class="{
              'bg-green-500': terminData.status_termin === 'completed',
              'bg-blue-500': terminData.status_termin === 'in_progress',
              'bg-gray-400': terminData.status_termin === 'not_started',
            }"
          ></div>
          <span
            class="text-xs font-medium"
            :class="{
              'text-green-600': terminData.status_termin === 'completed',
              'text-blue-600': terminData.status_termin === 'in_progress',
              'text-gray-600': terminData.status_termin === 'not_started',
            }"
          >
            {{
              terminData.status_termin === "completed"
                ? "Selesai"
                : terminData.status_termin === "in_progress"
                ? "Berjalan"
                : "Belum Dimulai"
            }}
          </span>
        </div>
        <span class="text-sm font-medium text-gray-900">
          {{ terminData.jumlah_termin_dibuat || 0 }} / {{ terminData.jumlah_termin || 0 }}
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-300"
          :class="{
            'bg-green-500': terminData.status_termin === 'completed',
            'bg-blue-500': terminData.status_termin === 'in_progress',
            'bg-gray-400': terminData.status_termin === 'not_started',
          }"
          :style="{
            width:
              ((terminData.jumlah_termin_dibuat || 0) / (terminData.jumlah_termin || 1)) *
                100 +
              '%',
          }"
        ></div>
      </div>
    </div>

    <!-- Financial Summary -->
    <div class="space-y-3 mb-4">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">Grand Total</span>
        <span class="text-sm font-medium text-gray-900">
          {{ formatCurrency(terminData.grand_total || 0) }}
        </span>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">Total Cicilan</span>
        <span class="text-sm font-medium text-blue-600">
          {{ formatCurrency(terminData.total_cicilan || 0) }}
        </span>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">No. Referensi</span>
        <span class="text-sm font-mono font-medium text-gray-900">
          {{ terminData.no_referensi || "-" }}
        </span>
      </div>
    </div>

    <!-- Sisa Pembayaran (Highlighted) -->
    <div class="border-t border-gray-200 pt-4">
      <div class="flex items-center justify-between">
        <span class="text-base font-semibold text-gray-900">Sisa Pembayaran</span>
        <span class="text-lg font-bold text-orange-600">
          {{ formatCurrency(terminData.sisa_pembayaran || 0) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatCurrency } from "@/lib/currencyUtils";

defineProps<{
  terminData: {
    no_referensi?: string;
    grand_total?: number;
    total_cicilan?: number;
    sisa_pembayaran?: number;
    jumlah_termin_dibuat?: number;
    jumlah_termin?: number;
    status_termin?: "completed" | "in_progress" | "not_started";
  };
}>();
</script>
