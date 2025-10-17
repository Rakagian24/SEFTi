<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <Calculator class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Ringkasan Pembayaran</h3>
    </div>

    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-600">Nominal</span>
        <span class="text-sm font-medium text-gray-900">
          {{ formatCurrency(paymentVoucher.nominal || 0) }}
        </span>
      </div>

      <div v-if="paymentVoucher.currency" class="flex items-center justify-between">
        <span class="text-sm text-gray-600">Mata Uang</span>
        <span class="text-sm font-medium text-gray-900">
          {{ paymentVoucher.currency }}
        </span>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between">
          <span class="text-lg font-semibold text-gray-900">Total</span>
          <span class="text-lg font-bold text-green-600">
            {{ formatCurrency(paymentVoucher.nominal || 0) }}
          </span>
        </div>
      </div>
    </div>

    <div class="mt-6 pt-6 border-t border-gray-200">
      <div class="text-center">
        <p class="text-xs text-gray-500 mb-2">Total Pembayaran</p>
        <p class="text-2xl font-bold text-indigo-600">
          {{ formatCurrency(paymentVoucher.nominal || 0) }}
        </p>
        <p v-if="paymentVoucher.currency" class="text-sm text-gray-600 mt-1">
          {{ paymentVoucher.currency }}
        </p>
      </div>
    </div>

    <!-- Creator Info -->
    <div v-if="paymentVoucher.creator" class="mt-6 pt-6 border-t border-gray-200">
      <p class="text-xs font-medium text-gray-500 mb-3">Dibuat Oleh</p>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
          <User class="w-5 h-5 text-indigo-600" />
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">
            {{ paymentVoucher.creator.name }}
          </p>
          <p class="text-xs text-gray-500">
            {{ formatDate(paymentVoucher.created_at) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Calculator, User } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";

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
