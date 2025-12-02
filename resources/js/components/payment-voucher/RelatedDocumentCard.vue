<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <FileText class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Dokumen Terkait</h3>
    </div>

    <!-- PO Anggaran (for tipe Anggaran) -->
    <div v-if="paymentVoucher.tipe_pv === 'Anggaran' && poAnggaran" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">PO Anggaran</p>
      <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">
              {{ poAnggaran.no_po_anggaran || '-' }}
            </p>
            <p class="text-sm text-gray-600">
              {{ poAnggaran.perihal?.nama || '-' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Departemen: {{ poAnggaran.department?.name || '-' }}
            </p>
          </div>
          <div class="text-right">
            <p class="font-medium text-gray-900">
              {{ formatCurrency(Number(poAnggaran.nominal ?? 0)) }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusClass(poAnggaran.status),
              ]"
            >
              {{ poAnggaran.status || '-' }}
            </span>
          </div>
        </div>
      </div>
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

    <!-- Memo Pembayaran (linked directly on PV) -->
    <div v-if="paymentVoucher.memo_pembayaran_id && memoPembayaran" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Memo Pembayaran</p>
      <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">
              {{ memoPembayaran.no_mb || memoPembayaran.no_memo || '-' }}
            </p>
            <p class="text-sm text-gray-600">
              {{ memoPembayaran.perihal?.nama || '-' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Departemen: {{ memoPembayaran.department?.name || '-' }}
            </p>
          </div>
          <div class="text-right">
            <p class="font-medium text-gray-900">
              {{ formatCurrency(Number(memoPembayaran.total ?? memoPembayaran.nominal ?? 0)) }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusClass(memoPembayaran.status),
              ]"
            >
              {{ memoPembayaran.status || '-' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Allocation Breakdown: BPB -->
    <div v-if="bpbAllocations.length > 0" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">BPB Terkait (Alokasi PV)</p>
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm text-gray-600">Total Dialokasikan</p>
          <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(totalBpbAllocated) }}</p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-xs">
            <thead>
              <tr class="text-left text-gray-600">
                <th class="py-1 pr-2">No. BPB</th>
                <th class="py-1 pr-2">Tanggal</th>
                <th class="py-1 pr-2">Dialokasikan</th>
                <th class="py-1 pr-2">Outstanding</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in bpbAllocations" :key="`bpb-${a.id || a.bpb_id}`" class="border-t border-gray-100">
                <td class="py-1 pr-2 font-medium">{{ a?.bpb?.no_bpb || `#${a?.bpb_id}` }}</td>
                <td class="py-1 pr-2">{{ formatDate(a?.bpb?.tanggal) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(a?.amount) || 0) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(a?.bpb?.outstanding) || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Allocation Breakdown: Memo Pembayaran -->
    <div v-if="memoAllocations.length > 0" class="mb-2">
      <p class="text-sm font-medium text-gray-700 mb-2">Memo Pembayaran Terkait (Alokasi PV)</p>
      <div class="border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm text-gray-600">Total Dialokasikan</p>
          <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(totalMemoAllocated) }}</p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-xs">
            <thead>
              <tr class="text-left text-gray-600">
                <th class="py-1 pr-2">No. Memo</th>
                <th class="py-1 pr-2">Tanggal</th>
                <th class="py-1 pr-2">Dialokasikan</th>
                <th class="py-1 pr-2">Outstanding</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in memoAllocations" :key="`memo-${a.id || a.memo_id}`" class="border-t border-gray-100">
                <td class="py-1 pr-2 font-medium">{{ a?.memo?.no_memo || a?.memo?.no_mb || `#${a?.memo_id}` }}</td>
                <td class="py-1 pr-2">{{ formatDate(a?.memo?.tanggal) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(a?.amount) || 0) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(a?.memo?.outstanding) || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- No Related Document -->
    <div
      v-if="!paymentVoucher.purchase_order_id && !paymentVoucher.memo_pembayaran_id && !paymentVoucher.po_anggaran_id && bpbAllocations.length === 0 && memoAllocations.length === 0"
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
const poAnggaran = computed(() => props.paymentVoucher.poAnggaran || props.paymentVoucher.po_anggaran);

const bpbAllocations = computed<any[]>(() => props.paymentVoucher.bpbAllocations || props.paymentVoucher.bpb_allocations || []);
const memoAllocations = computed<any[]>(() => props.paymentVoucher.memoAllocations || props.paymentVoucher.memo_allocations || []);

const totalBpbAllocated = computed<number>(() => (bpbAllocations.value || []).reduce((s, a: any) => s + (Number(a?.amount) || 0), 0));
const totalMemoAllocated = computed<number>(() => (memoAllocations.value || []).reduce((s, a: any) => s + (Number(a?.amount) || 0), 0));

function getStatusClass(status: string) {
  return getStatusBadgeClass(status);
}

function formatDate(dateString?: string) {
  if (!dateString) return "-";
  try {
    return new Date(dateString).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
  } catch {
    return dateString;
  }
}
</script>
