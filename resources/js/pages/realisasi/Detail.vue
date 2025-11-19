<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            type="button"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="goBack"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Realisasi</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4" />
              </svg>
              {{ realisasi.no_realisasi || '-' }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
            <span class="inline-block w-2 h-2 mr-2 rounded-full" :class="statusDotClass"></span>
            {{ realisasi.status || '-' }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Realisasi</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">No. Realisasi</p>
                  <p class="text-sm font-mono text-gray-900">{{ realisasi.no_realisasi || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Tanggal Realisasi</p>
                  <p class="text-sm text-gray-900">{{ formatDate(realisasi.tanggal) }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Departemen</p>
                  <p class="text-sm text-gray-900">{{ realisasi.department?.name || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Metode Pembayaran</p>
                  <p class="text-sm text-gray-900">{{ realisasi.metode_pembayaran || '-' }}</p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">Nama Bank</p>
                  <p class="text-sm text-gray-900">{{ realisasi.bank?.nama_bank || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Nama Rekening</p>
                  <p class="text-sm text-gray-900">{{ realisasi.nama_rekening || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">No. Rekening/VA</p>
                  <p class="text-sm font-mono text-gray-900">{{ realisasi.no_rekening || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Status</p>
                  <p class="text-sm text-gray-900">{{ realisasi.status || '-' }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Total Anggaran</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(realisasi.total_anggaran || 0) }}</p>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Total Realisasi</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(realisasi.total_realisasi || 0) }}</p>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-xs font-medium text-gray-500 mb-1">Catatan</p>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-900 leading-relaxed">{{ realisasi.note || '-' }}</p>
              </div>
            </div>
          </div>

          <div v-if="realisasi.poAnggaran" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Anggaran</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">No. PO Anggaran</p>
                  <p class="text-sm font-mono text-gray-900">{{ realisasi.poAnggaran?.no_po_anggaran || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Departemen</p>
                  <p class="text-sm text-gray-900">{{ realisasi.poAnggaran?.department?.name || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Tanggal</p>
                  <p class="text-sm text-gray-900">{{ formatDate(realisasi.poAnggaran?.tanggal) }}</p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">Metode Pembayaran</p>
                  <p class="text-sm text-gray-900">{{ realisasi.poAnggaran?.metode_pembayaran || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Nominal PO Anggaran</p>
                  <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(realisasi.poAnggaran?.nominal || 0) }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Status PO Anggaran</p>
                  <p class="text-sm text-gray-900">{{ realisasi.poAnggaran?.status || '-' }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-xs font-medium text-gray-500 mb-1">Catatan PO Anggaran</p>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-900 leading-relaxed">{{ realisasi.poAnggaran?.note || '-' }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Detail Realisasi</h3>
              <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                {{ (realisasi.items || []).length }} item
              </span>
            </div>

            <div class="overflow-hidden">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                      <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Keterangan</th>
                      <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                      <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Satuan</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga Anggaran</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal Anggaran</th>
                      <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Realisasi</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(it, idx) in realisasi.items || []" :key="idx" class="hover:bg-gray-50 transition-colors">
                      <td class="px-4 py-3 text-sm text-gray-900">{{ it.jenis_pengeluaran_text || '-' }}</td>
                      <td class="px-4 py-3 text-sm text-gray-700">{{ it.keterangan || '-' }}</td>
                      <td class="px-4 py-3 text-center text-sm text-gray-900">{{ formatQty(it.qty) }}</td>
                      <td class="px-4 py-3 text-center text-sm text-gray-600">{{ it.satuan || '-' }}</td>
                      <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatCurrency(it.harga || 0) }}</td>
                      <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatCurrency(it.subtotal ?? ((it.qty || 1) * (it.harga || 0))) }}</td>
                      <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ formatCurrency(it.realisasi || 0) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
            </div>

            <div class="space-y-3 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Total Anggaran</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(realisasi.total_anggaran || 0) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Total Realisasi</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(realisasi.total_realisasi || 0) }}</span>
              </div>
              <div class="border-t pt-3 flex items-center justify-between">
                <span class="text-gray-900 font-semibold">Selisih</span>
                <span class="text-gray-900 font-semibold">{{ formatCurrency((realisasi.total_anggaran || 0) - (realisasi.total_realisasi || 0)) }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col gap-3">
              <button
                type="button"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                @click="goBack"
              >
                Kembali
              </button>
              <button
                type="button"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="realisasi.status === 'Canceled'"
                @click="downloadPdf"
              >
                Unduh PDF
              </button>
              <button
                type="button"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                @click="goLog"
              >
                Lihat Log
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { formatCurrency } from '@/lib/currencyUtils';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any }>();
const realisasi = props.realisasi as any;
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Detail' }];

function formatDate(value?: string) {
  if (!value) return '-';
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatQty(val: any) {
  const n = Number(val);
  if (!isFinite(n) || isNaN(n)) return '1';
  const isInt = Math.floor(n) === n;
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: isInt ? 0 : 0,
    maximumFractionDigits: isInt ? 0 : 2,
  }).format(n);
}

const statusDotClass = computed(() => {
  const status = (realisasi?.status || '').toLowerCase();
  if (status === 'approved' || status === 'validated' || status === 'verified') return 'bg-green-500';
  if (status === 'pending' || status === 'draft') return 'bg-yellow-400';
  if (status === 'rejected' || status === 'canceled') return 'bg-red-500';
  return 'bg-gray-400';
});

function goBack() { history.back(); }
function goLog() { router.visit(`/realisasi/${props.realisasi.id}/log`); }
function downloadPdf() { window.open(`/realisasi/${props.realisasi.id}/download`, '_blank'); }
</script>

