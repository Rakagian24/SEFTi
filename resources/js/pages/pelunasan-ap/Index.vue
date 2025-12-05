<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Pelunasan AP</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Grid2x2Check class="w-4 h-4 mr-1" />
            Kelola Pelunasan AP
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="openConfirmSend"
            :disabled="!canSendSelected"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" />
            Kirim ({{ selected.length }})
          </button>

          <ConfirmDialog
            :show="showConfirmSend"
            :message="`Apakah Anda yakin ingin mengirim ${selected.length} Pelunasan AP?`"
            @confirm="confirmSend"
            @cancel="cancelSend"
          />

          <button
            @click="exportExcel"
            class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <Download class="w-4 h-4" />
            Export Excel
          </button>

          <button
            @click="goToAdd"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New
          </button>
        </div>
      </div>

      <PelunasanApFilter
        :filters="filters"
        :suppliers="suppliers"
        :departments="departments"
        :current-filters="currentFilters"
        :columns="columns"
        :entries-per-page="filters.per_page || 10"
        @filter="applyFilters"
        @reset="resetFilters"
        @update:columns="updateColumns"
        @update:entries-per-page="updateEntriesPerPage"
      />

      <PelunasanApTable
        :data="pelunasanAps?.data || []"
        :pagination="pelunasanAps"
        :selected="selected"
        :columns="columns"
        @select="onSelect"
        @action="handleAction"
        @paginate="handlePagination"
        @add="goToAdd"
      />

      <StatusLegend entity="Pelunasan AP" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import StatusLegend from '@/components/ui/StatusLegend.vue'
import PelunasanApFilter from '@/components/pelunasan-ap/PelunasanApFilter.vue'
import PelunasanApTable from '@/components/pelunasan-ap/PelunasanApTable.vue'
import { Grid2x2Check, Send, Download } from 'lucide-vue-next'
import { useMessagePanel } from '@/composables/useMessagePanel'

defineOptions({ layout: AppLayout })

interface Column {
  key: string
  label: string
  checked: boolean
  sortable?: boolean
}

const props = defineProps<{
  pelunasanAps: any
  filters: Record<string, any>
  suppliers: any[]
  departments: any[]
  columns: Column[]
}>()

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Pelunasan AP' }
]

const pelunasanAps = ref(props.pelunasanAps || { data: [], total: 0, current_page: 1, last_page: 1 })
const currentFilters = ref(props.filters || {})
const columns = ref<Column[]>(props.columns || [
  { key: 'no_pl', label: 'No. PL', checked: true, sortable: true },
  { key: 'tanggal', label: 'Tanggal', checked: true, sortable: true },
  { key: 'no_referensi', label: 'No. Referensi', checked: true },
  { key: 'supplier', label: 'Supplier', checked: true },
  { key: 'status', label: 'Status', checked: true, sortable: true },
])
const suppliers = ref(props.suppliers || [])
const departments = ref(props.departments || [])
const selected = ref<number[]>([])

// Global message panel integration
const page = usePage()
const { addSuccess, addError } = useMessagePanel()

watch(
  () => page.props,
  (newProps: any) => {
    const flash = newProps?.flash || {}
    if (typeof flash.success === 'string' && flash.success) addSuccess(flash.success)
    if (typeof flash.error === 'string' && flash.error) addError(flash.error)
  },
  { immediate: true }
)

const canSendSelected = computed(() =>
  selected.value.length > 0 &&
  (pelunasanAps.value?.data || [])
    .filter((p: any) => selected.value.includes(p.id))
    .every((row: any) => row.status === 'Draft' || row.status === 'Rejected')
)

async function loadPelunasanAps(params: Record<string, any> = {}) {
  const query = new URLSearchParams()
  Object.entries(currentFilters.value).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') query.set(k, String(v))
  })
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') query.set(k, String(v))
  })
  const response = await axios.get(`/pelunasan-ap?${query.toString()}`, {
    headers: { Accept: 'application/json' }
  })
  if (typeof response.data !== 'string') pelunasanAps.value = response.data
}

function applyFilters(payload: Record<string, any>) {
  currentFilters.value = {
    ...currentFilters.value,
    ...payload,
    per_page: payload.entriesPerPage || currentFilters.value.per_page || 10
  }
  loadPelunasanAps()
}

function resetFilters() {
  currentFilters.value = { per_page: 10 }
  loadPelunasanAps()
}

function updateColumns(newColumns: Column[]) {
  columns.value = newColumns
  currentFilters.value.columns = JSON.stringify(newColumns)
  loadPelunasanAps()
}

function updateEntriesPerPage(newPerPage: number) {
  currentFilters.value.per_page = newPerPage
  loadPelunasanAps()
}

function handlePagination(url: string) {
  if (!url) return
  const urlParams = new URLSearchParams(url.split('?')[1])
  const page = urlParams.get('page')
  if (page) {
    currentFilters.value.page = page
    loadPelunasanAps()
  }
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected
}

function handleAction(payload: { action: string; row: any }) {
  const { action, row } = payload
  if (action === 'edit') router.visit(`/pelunasan-ap/${row.id}/edit`)
  if (action === 'delete') {
    router.delete(`/pelunasan-ap/${row.id}`, {
      onSuccess: () => addSuccess('Pelunasan AP berhasil dihapus'),
      onError: () => addError('Terjadi kesalahan saat menghapus Pelunasan AP'),
    })
  }
  if (action === 'detail') router.visit(`/pelunasan-ap/${row.id}`)
  if (action === 'log') router.visit(`/pelunasan-ap/${row.id}/log`)
  if (action === 'download') window.open(`/pelunasan-ap/${row.id}/download`, '_blank')
}

const showConfirmSend = ref(false)

function openConfirmSend() {
  if (!canSendSelected.value) return
  showConfirmSend.value = true
}

function confirmSend() {
  router.post('/pelunasan-ap/send', { ids: selected.value }, {
    onSuccess: () => {
      selected.value = []
      loadPelunasanAps()
      addSuccess('Pelunasan AP berhasil dikirim')
    },
    onError: () => {
      addError('Terjadi kesalahan saat mengirim Pelunasan AP')
    },
    preserveScroll: true,
  })
  showConfirmSend.value = false
}

function cancelSend() {
  showConfirmSend.value = false
}

function exportExcel() {
  window.open('/pelunasan-ap/export', '_blank')
}

function goToAdd() {
  router.visit('/pelunasan-ap/create')
}
</script>
