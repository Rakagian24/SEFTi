<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail PO Anggaran</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4" />
              </svg>
              {{ poAnggaran?.no_po_anggaran || '-' }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(poAnggaran?.status)}`">
            <div class="w-2 h-2 rounded-full mr-2 inline-block" :class="getStatusDotClass(poAnggaran?.status)"></div>
            {{ poAnggaran?.status || '-' }}
          </span>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Anggaran</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Dokumen</p>
                    <p class="text-sm text-gray-600 font-mono">{{ poAnggaran?.no_po_anggaran || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">{{ poAnggaran?.department?.name || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v2m-6 4h6" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">{{ formatDate(poAnggaran?.tanggal) }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                    <p class="text-sm text-gray-600">{{ poAnggaran?.metode_pembayaran || '-' }}</p>
                  </div>
                </div>

                <template v-if="poAnggaran?.metode_pembayaran === 'Cek/Giro'">
                  <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-gray-900">No. Cek/Giro</p>
                      <p class="text-sm text-gray-600 font-mono">{{ poAnggaran?.no_giro || '-' }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-gray-900">Tanggal Giro</p>
                      <p class="text-sm text-gray-600">{{ formatDate(poAnggaran?.tanggal_giro) }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-gray-900">Tanggal Cair</p>
                      <p class="text-sm text-gray-600">{{ formatDate(poAnggaran?.tanggal_cair) }}</p>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Bisnis Partner Information -->
          <div v-if="poAnggaran?.bisnis_partner" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Bisnis Partner</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">Nama</p>
                  <p class="text-sm text-gray-700">{{ poAnggaran.bisnis_partner?.nama_bp || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Jenis</p>
                  <p class="text-sm text-gray-700">{{ poAnggaran.bisnis_partner?.jenis_bp || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Alamat</p>
                  <p class="text-sm text-gray-700 whitespace-pre-line">{{ poAnggaran.bisnis_partner?.alamat || '-' }}</p>
                </div>
              </div>
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">Email</p>
                  <p class="text-sm text-gray-700">{{ poAnggaran.bisnis_partner?.email || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Telepon</p>
                  <p class="text-sm text-gray-700">{{ poAnggaran.bisnis_partner?.no_telepon || '-' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Bank Information -->
          <div v-if="poAnggaran?.bisnis_partner" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Bank</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-3">
                <div>
                  <p class="text-sm font-medium text-gray-900">Nama Bank</p>
                  <p class="text-sm text-gray-700">{{ poAnggaran.bisnis_partner?.bank?.nama_bank || '-' }} <span class="text-xs text-gray-500">{{ poAnggaran.bisnis_partner?.bank?.singkatan ? `(${poAnggaran.bisnis_partner?.bank?.singkatan})` : '' }}</span></p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Nama Pemilik Rekening</p>
                  <p class="text-sm text-gray-700">{{ poAnggaran.bisnis_partner?.nama_rekening || '-' }}</p>
                </div>
              </div>
              <div class="space-y-3">
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Rekening/VA</p>
                  <p class="text-sm text-gray-700 font-mono">{{ poAnggaran.bisnis_partner?.no_rekening_va || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Status Bank</p>
                  <p class="text-sm text-gray-700 capitalize">{{ poAnggaran.bisnis_partner?.bank?.status || '-' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Detail Anggaran</h3>
              <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                {{ poAnggaran?.items?.length || 0 }} item
              </span>
            </div>

            <div class="overflow-hidden">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Keterangan</th>
                      <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                      <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Satuan</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(item, index) in poAnggaran?.items || []" :key="index" class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">{{ index + 1 }}</span>
                      </td>
                      <td class="px-6 py-4"><span class="text-sm font-medium text-gray-900">{{ item.jenis_pengeluaran_text || '-' }}</span></td>
                      <td class="px-6 py-4"><span class="text-sm text-gray-700">{{ item.keterangan || '-' }}</span></td>
                      <td class="px-6 py-4 text-center"><span class="text-sm text-gray-900">{{ formatQty(item.qty) }}</span></td>
                      <td class="px-6 py-4 text-center"><span class="text-sm text-gray-600">{{ item.satuan || '-' }}</span></td>
                      <td class="px-6 py-4 text-right"><span class="text-sm font-medium text-gray-900">{{ formatCurrency(item.harga || 0) }}</span></td>
                      <td class="px-6 py-4 text-right"><span class="text-sm font-semibold text-gray-900">{{ formatCurrency(item.subtotal ?? ((item.qty || 1) * (item.harga || 0))) }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Additional Information -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
            </div>

            <div class="space-y-6">
              <div>
                <p class="text-sm font-medium text-gray-900 mb-2">Catatan</p>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-900 leading-relaxed">{{ poAnggaran?.note || '-' }}</p>
                </div>
              </div>

              <div v-if="poAnggaran?.dokumen">
                <p class="text-sm font-medium text-gray-900 mb-2">Dokumen Terlampir</p>
                <div class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                  <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div class="flex-1">
                    <a :href="'/storage/' + poAnggaran.dokumen" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 underline">{{ (poAnggaran.dokumen || '').split('/').pop() }}</a>
                    <p class="text-xs text-gray-500 mt-1">Click to view document</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Summary & Approval Progress -->
        <div class="space-y-6">
          <!-- Approval Progress (read-only) -->
            <ApprovalProgress
              :progress="progress || []"
              :purchase-order="poAnggaran"
              :user-role="userRole || ''"
              :can-verify="false"
              :can-validate="false"
              :can-approve="false"
              :can-reject="false"
            />
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Total Detail Items</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(itemsTotal) }}</span>
              </div>
              <div class="border-t pt-3 flex items-center justify-between text-sm">
                <span class="text-gray-900 font-semibold">Nominal</span>
                <span class="text-gray-900 font-semibold">{{ formatCurrency(poAnggaran?.nominal || 0) }}</span>
              </div>
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
import ApprovalProgress from '@/components/approval/ApprovalProgress.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ poAnggaran: any; progress?: any[]; userRole?: string }>();
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'PO Anggaran', href: '/po-anggaran' }, { label: 'Detail' }];

function formatDate(value?: string) {
  if (!value) return '-';
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}

function getStatusBadgeClass(status?: string) {
  switch ((status || '').toLowerCase()) {
    case 'draft': return 'bg-gray-100 text-gray-700';
    case 'in progress': return 'bg-blue-100 text-blue-700';
    case 'verified': return 'bg-yellow-100 text-yellow-700';
    case 'validated': return 'bg-indigo-100 text-indigo-700';
    case 'approved': return 'bg-green-100 text-green-700';
    case 'rejected': return 'bg-red-100 text-red-700';
    default: return 'bg-gray-100 text-gray-700';
  }
}
function getStatusDotClass(status?: string) {
  switch ((status || '').toLowerCase()) {
    case 'draft': return 'bg-gray-500';
    case 'in progress': return 'bg-blue-600';
    case 'verified': return 'bg-yellow-600';
    case 'validated': return 'bg-indigo-600';
    case 'approved': return 'bg-green-600';
    case 'rejected': return 'bg-red-600';
    default: return 'bg-gray-400';
  }
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

const itemsTotal = computed(() => {
  const items = (props.poAnggaran?.items || []) as any[];
  return items.reduce((acc, it) => acc + Number(it.subtotal ?? (Number(it.qty || 1) * Number(it.harga || 0))), 0);
});
</script>
