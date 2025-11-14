<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <FileText class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Dokumen Terkait</h3>
    </div>

    <!-- Purchase Order -->
    <div v-if="paymentVoucher.purchase_order_id && purchaseOrder" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Purchase Order</p>
      <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">
              {{ purchaseOrder.no_po || "-" }}
            </p>
            <p class="text-sm text-gray-600">
              {{ purchaseOrder.perihal?.nama || "-" }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Supplier: {{ purchaseOrder.supplier?.nama_supplier || "-" }}
            </p>
          </div>
          <div class="text-right">
            <p class="font-medium text-gray-900">
              {{ formatCurrency(Number(purchaseOrder.grand_total ?? purchaseOrder.total ?? 0)) }}
            </p>
            <!-- Finance summary when outstanding is available -->
            <div v-if="typeof purchaseOrder.outstanding !== 'undefined' && purchaseOrder.outstanding !== null" class="mt-1 space-y-0.5">
              <p class="text-xs text-gray-600">
                Dibayar (PV):
                <span class="font-medium text-gray-900">
                  {{ formatCurrency(Math.max(0, (Number(purchaseOrder.grand_total ?? purchaseOrder.total ?? 0) - Number(purchaseOrder.outstanding ?? 0)))) }}
                </span>
              </p>
              <p class="text-xs font-semibold text-indigo-700">
                Outstanding:
                <span class="font-bold">
                  {{ formatCurrency(Math.max(0, Number(purchaseOrder.outstanding || 0))) }}
                </span>
              </p>
            </div>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusClass(purchaseOrder.status),
              ]"
            >
              {{ purchaseOrder.status || "-" }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Memo Pembayaran -->
    <div v-if="paymentVoucher.memo_pembayaran_id && memoPembayaran">
      <p class="text-sm font-medium text-gray-700 mb-2">Memo Pembayaran</p>
      <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">
              {{ memoPembayaran.no_mb || "-" }}
            </p>
            <p class="text-sm text-gray-600">
              {{ memoPembayaran.perihal?.nama || "-" }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Departemen: {{ memoPembayaran.department?.name || "-" }}
            </p>
          </div>
          <div class="text-right">
            <p class="font-medium text-gray-900">
              {{ formatCurrency(memoPembayaran.total || 0) }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusClass(memoPembayaran.status),
              ]"
            >
              {{ memoPembayaran.status || "-" }}
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
import { computed } from "vue";
import { FileText } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";
import { getStatusBadgeClass } from "@/lib/status";

const props = defineProps<{
  paymentVoucher: any;
}>();

const purchaseOrder = computed(() => props.paymentVoucher.purchaseOrder || props.paymentVoucher.purchase_order);
const memoPembayaran = computed(() => props.paymentVoucher.memoPembayaran || props.paymentVoucher.memo_pembayaran);

function getStatusClass(status: string) {
  return getStatusBadgeClass(status);
}
</script>
