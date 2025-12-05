<template>
  <AppLayout>
    <template #header>
      <PageHeader title="Detail Pelunasan" />
    </template>

    <div class="space-y-6">
      <!-- Action Buttons -->
      <div class="flex gap-2 flex-wrap">
        <Link
          v-if="pelunasanAp.status === 'Draft'"
          :href="route('pelunasan-ap.edit', pelunasanAp.id)"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
        >
          <Icon name="pencil" class="w-4 h-4" />
          Edit
        </Link>
        <button
          v-if="pelunasanAp.status === 'Draft'"
          @click="cancelPelunasanAp"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2"
        >
          <Icon name="trash-2" class="w-4 h-4" />
          Batalkan
        </button>
        <Link
          :href="route('pelunasan-ap.log', pelunasanAp.id)"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2"
        >
          <Icon name="history" class="w-4 h-4" />
          Log Aktivitas
        </Link>
        <Link
          href="/pelunasan-ap"
          class="px-4 py-2 border rounded-lg hover:bg-gray-50"
        >
          Kembali
        </Link>
      </div>

      <!-- Basic Info -->
      <div class="bg-white rounded-lg border p-6 space-y-4">
        <h3 class="text-lg font-semibold">Informasi Dasar</h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">No. Pelunasan</label>
            <p class="text-gray-900">{{ pelunasanAp.no_pl || '-' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
            <p class="text-gray-900">{{ formatDate(pelunasanAp.tanggal) }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tipe Pelunasan</label>
            <p class="text-gray-900">{{ pelunasanAp.tipe_pelunasan }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <span :class="getStatusClass(pelunasanAp.status)" class="px-3 py-1 rounded-full text-xs font-medium">
              {{ pelunasanAp.status }}
            </span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Supplier</label>
            <p class="text-gray-900">{{ pelunasanAp.supplier?.nama }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Departemen</label>
            <p class="text-gray-900">{{ pelunasanAp.department?.nama }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Nilai Dokumen Referensi</label>
            <p class="text-gray-900">{{ formatCurrency(pelunasanAp.nilai_dokumen_referensi) }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Dibuat Oleh</label>
            <p class="text-gray-900">{{ pelunasanAp.creator?.name }}</p>
          </div>
        </div>

        <div v-if="pelunasanAp.keterangan">
          <label class="block text-sm font-medium text-gray-700">Keterangan</label>
          <p class="text-gray-900">{{ pelunasanAp.keterangan }}</p>
        </div>
      </div>

      <!-- Payment Vouchers -->
      <div class="bg-white rounded-lg border p-6 space-y-4">
        <h3 class="text-lg font-semibold">Daftar Payment Voucher</h3>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-4 py-2 text-left">Tanggal</th>
                <th class="px-4 py-2 text-left">No. PV</th>
                <th class="px-4 py-2 text-right">Nilai PV</th>
                <th class="px-4 py-2 text-right">Outstanding</th>
                <th class="px-4 py-2 text-right">Nilai Pelunasan</th>
                <th class="px-4 py-2 text-right">Sisa</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="item in pelunasanAp.items" :key="item.id">
                <td class="px-4 py-2">{{ formatDate(item.payment_voucher?.tanggal) }}</td>
                <td class="px-4 py-2">{{ item.payment_voucher?.no_pv }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.nilai_pv) }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.outstanding) }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.nilai_pelunasan) }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.sisa) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 gap-4 pt-4 border-t">
          <div class="space-y-2">
            <div class="flex justify-between">
              <span>Total PV:</span>
              <span class="font-semibold">{{ formatCurrency(totalPV) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Total Alokasi:</span>
              <span class="font-semibold">{{ formatCurrency(totalAlokasi) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Total Sisa PV:</span>
              <span class="font-semibold">{{ formatCurrency(totalSisa) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import Icon from '@/components/Icon.vue'

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
  supplier?: { nama?: string } | null
  department?: { nama?: string } | null
  creator?: { name?: string } | null
  items: PelunasanApItemRow[]
}

const props = defineProps<{
  pelunasanAp: PelunasanApDetail
}>()

const totalPV = computed(() => {
  return props.pelunasanAp.items?.reduce((sum: number, item: PelunasanApItemRow) => sum + (item.nilai_pv || 0), 0) || 0
})

const totalAlokasi = computed(() => {
  return props.pelunasanAp.items?.reduce((sum: number, item: PelunasanApItemRow) => sum + (item.nilai_pelunasan || 0), 0) || 0
})

const totalSisa = computed(() => {
  return props.pelunasanAp.items?.reduce((sum: number, item: PelunasanApItemRow) => sum + (item.sisa || 0), 0) || 0
})

const formatDate = (date: any) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID')
}

const formatCurrency = (value: any) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const getStatusClass = (status: string) => {
  const classes: any = {
    'Draft': 'bg-yellow-100 text-yellow-800',
    'In Progress': 'bg-blue-100 text-blue-800',
    'Approved': 'bg-green-100 text-green-800',
    'Rejected': 'bg-red-100 text-red-800',
    'Canceled': 'bg-gray-100 text-gray-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const cancelPelunasanAp = () => {
  if (confirm('Apakah Anda yakin ingin membatalkan dokumen ini?')) {
    router.post(route('pelunasan-ap.cancel', props.pelunasanAp.id))
  }
}
</script>
