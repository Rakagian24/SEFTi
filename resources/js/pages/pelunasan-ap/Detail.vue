<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Pelunasan</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <TicketPercent class="w-4 h-4 mr-1" />
              {{ pelunasanAp.no_pl || "Draft" }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span
            :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
              pelunasanAp.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(pelunasanAp.status)"
            ></div>
            {{ pelunasanAp.status }}
          </span>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Informasi Dasar</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">No. Pelunasan</label>
                  <p class="text-base text-gray-900">{{ pelunasanAp.no_pl || '-' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal</label>
                  <p class="text-base text-gray-900">{{ formatDate(pelunasanAp.tanggal) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Tipe Pelunasan</label>
                  <p class="text-base text-gray-900">{{ pelunasanAp.tipe_pelunasan }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Supplier</label>
                  <p class="text-base text-gray-900">{{ pelunasanAp.supplier?.nama_supplier || '-' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Departemen</label>
                  <p class="text-base text-gray-900">{{ pelunasanAp.department?.name || '-' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Nilai Dokumen Referensi</label>
                  <p class="text-base text-gray-900 font-semibold">{{ formatCurrency(pelunasanAp.nilai_dokumen_referensi) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Dibuat Oleh</label>
                  <p class="text-base text-gray-900">{{ pelunasanAp.creator?.name || '-' }}</p>
                </div>
              </div>

              <div v-if="pelunasanAp.keterangan" class="pt-2">
                <label class="block text-sm font-medium text-gray-500 mb-1">Keterangan</label>
                <p class="text-base text-gray-900">{{ pelunasanAp.keterangan }}</p>
              </div>
            </div>
          </div>

          <!-- Payment Vouchers Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Daftar Payment Voucher</h3>
            </div>
            <div class="px-6 py-4">
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. PV</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai PV</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Pelunasan</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in pelunasanAp.items" :key="item.id" class="hover:bg-gray-50">
                      <td class="px-4 py-3 text-gray-900">{{ formatDate(item.payment_voucher?.tanggal) }}</td>
                      <td class="px-4 py-3 text-gray-900">{{ item.payment_voucher?.no_pv || '-' }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(item.nilai_pv) }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(item.outstanding) }}</td>
                      <td class="px-4 py-3 text-right text-gray-900 font-semibold">{{ formatCurrency(item.nilai_pelunasan) }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(item.sisa) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Card (Right Sidebar) -->
        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
            </div>
            <div class="px-6 py-4 space-y-3">
              <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-600">Total PV:</span>
                <span class="text-base font-semibold text-gray-900">{{ formatCurrency(totalPV) }}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-t border-gray-100">
                <span class="text-sm text-gray-600">Total Alokasi:</span>
                <span class="text-base font-semibold text-gray-900">{{ formatCurrency(totalAlokasi) }}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-t border-gray-100">
                <span class="text-sm text-gray-600">Total Sisa PV:</span>
                <span class="text-base font-semibold text-gray-900">{{ formatCurrency(totalSisa) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

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
          Kembali ke Daftar Pelunasan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue'
import { TicketPercent } from 'lucide-vue-next'
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from '@/lib/status'

interface PelunasanApItemRow {
  id: number
  nilai_pv?: number
  outstanding?: number
  nilai_pelunasan?: number
  sisa?: number
  payment_voucher?: {
    tanggal?: string
    no_pv?: string
  } | null
}

interface PelunasanApDetail {
  id: number
  no_pl?: string
  tanggal?: string
  tipe_pelunasan?: string
  status: string
  nilai_dokumen_referensi?: number
  keterangan?: string | null
  supplier?: { nama?: string; nama_supplier?: string } | null
  department?: { nama?: string; name?: string } | null
  creator?: { name?: string } | null
  items: PelunasanApItemRow[]
}

const props = defineProps<{
  pelunasanAp: PelunasanApDetail
}>()

usePage()

defineOptions({ layout: AppLayout })

const breadcrumbs = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Pelunasan", href: "/pelunasan-ap" },
  { label: "Detail", href: "#" },
])

const totalPV = computed(() => {
  return props.pelunasanAp.items?.reduce(
    (sum: number, item: PelunasanApItemRow) => sum + (Number(item.nilai_pv ?? 0) || 0),
    0
  ) || 0
})

const totalAlokasi = computed(() => {
  return props.pelunasanAp.items?.reduce(
    (sum: number, item: PelunasanApItemRow) => sum + (Number(item.nilai_pelunasan ?? 0) || 0),
    0
  ) || 0
})

const totalSisa = computed(() => {
  return props.pelunasanAp.items?.reduce(
    (sum: number, item: PelunasanApItemRow) => sum + (Number(item.sisa ?? 0) || 0),
    0
  ) || 0
})

const formatDate = (date: any) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const formatCurrency = (value: any) => {
  const numeric = Number(value ?? 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(numeric)
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status)
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status)
}

function goBack() {
  router.visit('/pelunasan-ap')
}
</script>
