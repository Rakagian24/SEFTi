<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Detail Memo Pembayaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Detail dokumen Memo Pembayaran
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="['In Progress', 'Approved'].includes(memoPembayaran.status)"
            @click="downloadDocument"
            class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <Download class="w-4 h-4" />
            Unduh PDF
          </button>

          <button
            @click="viewLog"
            class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <History class="w-4 h-4" />
            Log Activity
          </button>

          <button
            @click="goBack"
            class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeft class="w-4 h-4" />
            Kembali
          </button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <!-- Header Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Dokumen</h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">No. MB</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.no_mb || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Status</span>
                <span
                  :class="getStatusClass(memoPembayaran.status)"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ memoPembayaran.status }}
                </span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Tanggal</span>
                <span class="text-sm text-gray-900">{{ formatDate(memoPembayaran.tanggal) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Departemen</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.department?.name || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Perihal</span>
                <span class="text-sm text-gray-900">{{ getPerihalFromPurchaseOrders() || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">No. PO</span>
                <span class="text-sm text-gray-900">
                  {{ memoPembayaran.purchase_orders?.length > 0 ? memoPembayaran.purchase_orders.map((po: any) => po.no_po).join(', ') : '-' }}
                </span>
              </div>
            </div>
          </div>

          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Pembayaran</h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Metode Pembayaran</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.metode_pembayaran || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Total</span>
                <span class="text-sm text-gray-900">{{ formatCurrency(memoPembayaran.total) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Diskon</span>
                <span class="text-sm text-gray-900">{{ formatCurrency(memoPembayaran.diskon) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">PPN (11%)</span>
                <span class="text-sm text-gray-900">{{ formatCurrency(memoPembayaran.ppn_nominal) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">PPH</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.pph?.nama || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Nominal PPH</span>
                <span class="text-sm text-gray-900">{{ formatCurrency(memoPembayaran.pph_nominal) }}</span>
              </div>
              <div class="flex items-center justify-between py-2 border-t border-gray-200 pt-2">
                <span class="text-sm font-bold text-gray-900">Grand Total</span>
                <span class="text-sm font-bold text-gray-900">{{ formatCurrency(memoPembayaran.grand_total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Detail Keperluan -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Keperluan</h3>
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ memoPembayaran.detail_keperluan || '-' }}</p>
          </div>
        </div>

        <!-- Payment Method Specific Information -->
        <div v-if="memoPembayaran.metode_pembayaran === 'Transfer'" class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Transfer</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Supplier</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.supplier?.nama_supplier || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Bank</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.bank?.nama_bank || '-' }}</span>
              </div>
            </div>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Nama Rekening</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.nama_rekening || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">No. Rekening</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.no_rekening || '-' }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="memoPembayaran.metode_pembayaran === 'Cek/Giro'" class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Cek/Giro</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">No. Cek/Giro</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.no_giro || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Tanggal Giro</span>
                <span class="text-sm text-gray-900">{{ formatDate(memoPembayaran.tanggal_giro) }}</span>
              </div>
            </div>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Tanggal Cair</span>
                <span class="text-sm text-gray-900">{{ formatDate(memoPembayaran.tanggal_cair) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="memoPembayaran.metode_pembayaran === 'Kredit'" class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Kredit</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2">
              <span class="text-sm font-medium text-gray-500">No. Kartu Kredit</span>
              <span class="text-sm text-gray-900">{{ memoPembayaran.no_kartu_kredit || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Keterangan -->
        <div v-if="memoPembayaran.keterangan" class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Keterangan</h3>
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ memoPembayaran.keterangan }}</p>
          </div>
        </div>

        <!-- User Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Dibuat Oleh</h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Nama</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.creator?.name || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Tanggal Dibuat</span>
                <span class="text-sm text-gray-900">{{ formatDateTime(memoPembayaran.created_at) }}</span>
              </div>
            </div>
          </div>

          <div v-if="memoPembayaran.approver">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Disetujui Oleh</h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Nama</span>
                <span class="text-sm text-gray-900">{{ memoPembayaran.approver?.name || '-' }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm font-medium text-gray-500">Tanggal Disetujui</span>
                <span class="text-sm text-gray-900">{{ formatDateTime(memoPembayaran.approved_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards, Download, History, ArrowLeft } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Memo Pembayaran", href: "/memo-pembayaran" },
  { label: "Detail" }
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  memoPembayaran: any;
}>();

const memoPembayaran = ref(props.memoPembayaran);

function goBack() {
  router.visit('/memo-pembayaran');
}

function downloadDocument() {
  window.open(`/memo-pembayaran/${memoPembayaran.value.id}/download`, '_blank');
}

function viewLog() {
  router.visit(`/memo-pembayaran/${memoPembayaran.value.id}/log`);
}

function formatDate(date: string) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

function formatDateTime(dateTime: string) {
  if (!dateTime) return '-';
  return new Date(dateTime).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getStatusClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getPerihalFromPurchaseOrders() {
  if (!memoPembayaran.value.purchase_orders || memoPembayaran.value.purchase_orders.length === 0) {
    return null;
  }

  // Get perihal from the first purchase order that has perihal data
  const poWithPerihal = memoPembayaran.value.purchase_orders.find((po: any) => po.perihal?.nama);
  return poWithPerihal?.perihal?.nama || null;
}
</script>
