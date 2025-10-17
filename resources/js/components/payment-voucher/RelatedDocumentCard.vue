<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <FileText class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Dokumen Terkait</h3>
    </div>

    <!-- Purchase Order -->
    <div v-if="paymentVoucher.purchase_order_id && paymentVoucher.purchaseOrder" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Purchase Order</p>
      <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">
              {{ paymentVoucher.purchaseOrder.no_po || "-" }}
            </p>
            <p class="text-sm text-gray-600">
              {{ paymentVoucher.purchaseOrder.perihal?.nama || "-" }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Supplier: {{ paymentVoucher.purchaseOrder.supplier?.nama_supplier || "-" }}
            </p>
          </div>
          <div class="text-right">
            <p class="font-medium text-gray-900">
              {{ formatCurrency(paymentVoucher.purchaseOrder.total || 0) }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusClass(paymentVoucher.purchaseOrder.status),
              ]"
            >
              {{ paymentVoucher.purchaseOrder.status || "-" }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Memo Pembayaran -->
    <div v-if="paymentVoucher.memo_pembayaran_id && paymentVoucher.memoPembayaran">
      <p class="text-sm font-medium text-gray-700 mb-2">Memo Pembayaran</p>
      <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">
              {{ paymentVoucher.memoPembayaran.no_mb || "-" }}
            </p>
            <p class="text-sm text-gray-600">
              {{ paymentVoucher.memoPembayaran.perihal?.nama || "-" }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Departemen: {{ paymentVoucher.memoPembayaran.department?.name || "-" }}
            </p>
          </div>
          <div class="text-right">
            <p class="font-medium text-gray-900">
              {{ formatCurrency(paymentVoucher.memoPembayaran.total || 0) }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusClass(paymentVoucher.memoPembayaran.status),
              ]"
            >
              {{ paymentVoucher.memoPembayaran.status || "-" }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- No Related Document -->
    <div
      v-if="!paymentVoucher.purchase_order_id && !paymentVoucher.memo_pembayaran_id"
      class="text-center py-8 text-gray-500"
    >
      <FileText class="w-12 h-12 mx-auto text-gray-400 mb-2" />
      <p>Tidak ada dokumen terkait</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FileText } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";
import { getStatusBadgeClass } from "@/lib/status";

defineProps<{
  paymentVoucher: any;
}>();

function getStatusClass(status: string) {
  return getStatusBadgeClass(status);
}
</script>
