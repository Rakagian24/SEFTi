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
    <div class="mb-4">
      <div class="flex justify-between text-sm text-gray-600 mb-2">
        <span>Progress Termin</span>
        <span class="font-medium text-gray-900">
          {{ terminData.jumlah_termin_dibuat || 0 }} / {{ terminData.jumlah_termin || 0 }}
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div
          class="h-2.5 rounded-full transition-all duration-300"
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

    <!-- Summary Blocks -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-500 mb-1">Grand Total</div>
        <div class="text-xl font-bold text-gray-900">
          {{ formatCurrency(terminData.grand_total || 0) }}
        </div>
      </div>
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-500 mb-1">Total Cicilan</div>
        <div class="text-xl font-bold text-blue-600">
          {{ formatCurrency(terminData.total_cicilan || 0) }}
        </div>
      </div>
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-500 mb-1">Sisa Pembayaran</div>
        <div class="text-xl font-bold text-orange-600">
          {{ formatCurrency(terminData.sisa_pembayaran || 0) }}
        </div>
      </div>
    </div>

    <!-- Status & No Referensi -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="flex items-center gap-2">
        <div
          class="w-2.5 h-2.5 rounded-full flex-shrink-0"
          :class="{
            'bg-green-500': terminData.status_termin === 'completed',
            'bg-blue-500': terminData.status_termin === 'in_progress',
            'bg-gray-400': terminData.status_termin === 'not_started',
          }"
        ></div>
        <span
          class="font-semibold text-sm"
          :class="{
            'text-green-600': terminData.status_termin === 'completed',
            'text-blue-600': terminData.status_termin === 'in_progress',
            'text-gray-600': terminData.status_termin === 'not_started',
          }"
        >
          {{
            terminData.status_termin === "completed"
              ? "Termin Selesai"
              : terminData.status_termin === "in_progress"
              ? "Termin Sedang Berjalan"
              : "Termin Belum Dimulai"
          }}
        </span>
      </div>
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="text-sm text-gray-500 mb-1">No. Referensi</div>
        <div class="font-mono text-sm font-semibold text-gray-900 break-all">
          {{ terminData.no_referensi || "-" }}
        </div>
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
