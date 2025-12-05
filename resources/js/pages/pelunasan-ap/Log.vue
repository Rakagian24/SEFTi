<template>
  <AppLayout>
    <template #header>
      <PageHeader title="Log Aktivitas Pelunasan" />
    </template>

    <div class="space-y-6">
      <!-- Back Button -->
      <Link
        :href="route('pelunasan-ap.show', pelunasanAp.id)"
        class="px-4 py-2 border rounded-lg hover:bg-gray-50 inline-block"
      >
        Kembali
      </Link>

      <!-- Document Info -->
      <div class="bg-white rounded-lg border p-6">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">No. Pelunasan</label>
            <p class="text-gray-900">{{ pelunasanAp.no_pl || '-' }}</p>
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
            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
            <p class="text-gray-900">{{ formatDate(pelunasanAp.tanggal) }}</p>
          </div>
        </div>
      </div>

      <!-- Activity Logs -->
      <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold mb-4">Riwayat Aktivitas</h3>

        <div class="space-y-4">
          <div v-for="log in logs" :key="log.id" class="border-l-4 border-blue-500 pl-4 py-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-semibold text-gray-900">{{ log.action }}</p>
                <p class="text-sm text-gray-600">{{ log.description }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900">{{ log.user?.name }}</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(log.created_at) }}</p>
              </div>
            </div>
            <div v-if="log.changes" class="mt-2 text-sm text-gray-600 bg-gray-50 p-2 rounded">
              <p class="font-medium">Perubahan:</p>
              <pre class="text-xs">{{ JSON.stringify(log.changes, null, 2) }}</pre>
            </div>
          </div>

          <div v-if="logs.length === 0" class="text-center py-8 text-gray-500">
            Tidak ada riwayat aktivitas
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'

interface PelunasanApLogRow {
  id: number
  action?: string
  description?: string
  created_at?: string
  user?: { name?: string } | null
  changes?: any
}

interface PelunasanApLogHeader {
  id: number
  no_pl?: string
  status: string
  supplier?: { nama?: string } | null
  tanggal?: string
}

const { pelunasanAp, logs } = defineProps<{
  pelunasanAp: PelunasanApLogHeader
  logs: PelunasanApLogRow[]
}>()

const formatDate = (date: any) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID')
}

const formatDateTime = (datetime: any) => {
  if (!datetime) return '-'
  return new Date(datetime).toLocaleString('id-ID')
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
</script>
