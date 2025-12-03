<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { FileText } from 'lucide-vue-next';
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from '@/lib/status';
import ApprovalProgress from '@/components/approval/ApprovalProgress.vue';
import { useApi } from '@/composables/useApi';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any }>();
const { get } = useApi();
const page = usePage();
const userRole = ref<string>(((page.props as any)?.auth?.user?.role?.name) || '');
const approvalProgress = ref<any[]>([]);

function buildFallbackProgress() {
  let stepStatus: 'pending' | 'current' | 'completed' | 'rejected' = 'pending';
  switch (props.bpb?.status) {
    case 'Approved':
      stepStatus = 'completed';
      break;
    case 'In Progress':
      stepStatus = 'current';
      break;
    case 'Rejected':
      stepStatus = 'rejected';
      break;
    default:
      stepStatus = 'pending';
  }
  return [
    {
      step: 'approved',
      role: 'Kabag',
      status: stepStatus,
    },
  ];
}

const breadcrumbs = computed(() => [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: props.bpb?.no_bpb || `BPB #${props.bpb?.id}` },
]);

function formatDate(date: string | null) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
}

function formatInteger(value?: number | string) {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0));
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}

function goBack() {
  router.visit('/bpb');
}

function goToLog() {
  router.visit(`/bpb/${props.bpb?.id}/log`);
}

onMounted(async () => {
  try {
    const data = await get(`/api/approval/bpbs/${props.bpb?.id}/progress`);
    const steps = data?.progress || [];
    approvalProgress.value = steps.length ? steps : buildFallbackProgress();
  } catch (e) {
    console.error('Error fetching approval progress:', e);
  }
});
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail BPB</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <FileText class="w-4 h-4 mr-1" />
              {{ props.bpb?.no_bpb || `BPB #${props.bpb?.id}` }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span
            :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
              props.bpb?.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(props.bpb?.status)"
            ></div>
            {{ props.bpb?.status }}
          </span>

          <!-- Log Button -->
          <button
            @click="goToLog"
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 17v-6h13M9 7h13M4 7h.01M4 17h.01"
              />
            </svg>
            Log
          </button>
        </div>
      </div>

      <!-- Rejection Reason Card -->
      <div
        v-if="props.bpb?.status === 'Rejected' && props.bpb?.rejection_reason"
        class="bg-white rounded-lg shadow-sm border border-red-200 p-6 mb-6"
      >
        <div class="flex items-start gap-2">
          <svg
            class="w-5 h-5 text-red-500 mt-0.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"
            />
          </svg>
          <div>
            <h3 class="text-sm font-semibold text-red-700">Alasan Penolakan</h3>
            <p class="text-sm text-red-700 mt-1 whitespace-pre-wrap">
              {{ props.bpb.rejection_reason }}
            </p>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
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
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi BPB</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. BPB</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ props.bpb?.no_bpb || '-' }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 17v-6h13M9 7h13M4 7h.01M4 17h.01"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Surat Jalan</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ props.bpb?.surat_jalan_no || '-' }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(props.bpb?.tanggal) }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">
                      {{ props.bpb?.department?.name || '-' }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Supplier</p>
                    <p class="text-sm text-gray-600">
                      {{ props.bpb?.supplier?.nama_supplier || '-' }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. PV</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ props.bpb?.payment_voucher?.no_pv || '-' }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 20h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Invoice</p>
                    <p class="text-sm text-gray-600 font-mono">{{ props.bpb?.purchase_order?.no_invoice || '-' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Related Purchase Order Information Card -->
          <div
            v-if="props.bpb?.purchase_order"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
          >
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
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Terkait</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 20h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. PO</p>
                    <p class="text-sm text-gray-600 font-mono">{{ props.bpb?.purchase_order?.no_po || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal PO</p>
                    <p class="text-sm text-gray-600">{{ formatDate(props.bpb?.purchase_order?.tanggal || null) }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4m8 0a4 4 0 11-8 0 4 4 0 018 0zm-8 6h8" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                    <p class="text-sm text-gray-600">{{ props.bpb?.purchase_order?.metode_pembayaran || '-' }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Perihal</p>
                    <p class="text-sm text-gray-600">{{ props.bpb?.purchase_order?.perihal?.nama || '-' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="(props.bpb?.purchase_order?.items || []).length" class="mt-6">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h4 class="text-base font-semibold text-gray-900">Items PO</h4>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Nama Barang</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Qty</th>
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Satuan</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Harga</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="(poIt, idx) in props.bpb?.purchase_order?.items || []" :key="idx" class="hover:bg-gray-50">
                      <td class="px-4 py-3 text-gray-900">{{ poIt.nama_barang }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatInteger(poIt.qty) }}</td>
                      <td class="px-4 py-3 text-gray-600">{{ poIt.satuan }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(Number(poIt.harga)) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-gray-900">
                        {{ formatCurrency(Number(poIt.qty) * Number(poIt.harga)) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Items Table Card -->
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
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Daftar Barang Diterima</h3>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left font-semibold text-gray-900">Nama Barang</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-900">Qty</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-900">Satuan</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-900">Harga</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="(it, idx) in props.bpb?.items || []" :key="idx" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-900">{{ it.nama_barang }}</td>
                    <td class="px-4 py-3 text-right text-gray-900">{{ formatInteger(it.qty) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ it.satuan }}</td>
                    <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(Number(it.harga)) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                      {{ formatCurrency(Number(it.qty) * Number(it.harga)) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Additional Information -->
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
              <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
            </div>

            <div
            v-if="props.bpb?.keterangan"
            class="space-y-6">
              <div>
                <p class="text-sm font-medium text-gray-900 mb-2">Keterangan</p>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
                    {{ props.bpb?.keterangan }}
                  </p>
                </div>
              </div>
            </div>
            <div v-if="props.bpb?.surat_jalan_file">
                <p class="text-sm font-medium text-gray-900 mb-2">Dokumen Invoice</p>
                <div
                  class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200"
                >
                  <svg
                    class="w-8 h-8 text-blue-600"
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
                  <div class="flex-1">
                    <a
                      :href="'/storage/' + props.bpb?.surat_jalan_file"
                      target="_blank"
                      class="text-sm font-medium text-blue-600 hover:text-blue-800 underline"
                    >
                      {{ props.bpb?.surat_jalan_file.split("/").pop() }}
                    </a>
                    <p class="text-xs text-gray-500 mt-1">Click to view document</p>
                  </div>
                </div>
              </div>
          </div>
        </div>

        <!-- Right Column - Summary -->
        <div class="space-y-6">
          <ApprovalProgress
            :progress="approvalProgress"
            :purchase-order="props.bpb"
            :user-role="userRole"
          />

          <!-- Financial Summary Card -->
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
                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan Keuangan</h3>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">Subtotal</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(Number(props.bpb?.subtotal || 0)) }}
                </span>
              </div>

              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">Diskon</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(Number(props.bpb?.diskon || 0)) }}
                </span>
              </div>

              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">DPP</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(Number(props.bpb?.dpp || 0)) }}
                </span>
              </div>

              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">PPN</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(Number(props.bpb?.ppn || 0)) }}
                </span>
              </div>

              <!-- <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">PPH</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(Number(props.bpb?.pph || 0)) }}
                </span>
              </div> -->

              <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                  <span class="text-lg font-bold text-green-600">
                    {{ formatCurrency(Number(props.bpb?.grand_total || 0)) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="text-center">
                <p class="text-xs text-gray-500 mb-2">Total Pembayaran</p>
                <p class="text-2xl font-bold text-indigo-600">
                  {{ formatCurrency(Number(props.bpb?.grand_total || 0)) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Kembali ke Daftar BPB
        </button>
      </div>
    </div>
  </div>
</template>
