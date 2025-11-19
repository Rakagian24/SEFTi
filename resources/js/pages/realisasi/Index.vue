<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Realisasi</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Grid2x2Check class="w-4 h-4 mr-1" />
            Kelola Realisasi
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

          <ConfirmDialog :show="showConfirmSend" :message="`Apakah Anda yakin ingin mengirim ${selected.length} Realisasi?`" @confirm="confirmSend" @cancel="cancelSend" />

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

      <RealisasiFilter :filters="filters" :departments="departments" :columns="columns" :entries-per-page="filters.per_page || 10" @filter="applyFilters" @reset="resetFilters" @update:columns="updateColumns" @update:entries-per-page="updateEntriesPerPage" />

      <RealisasiTable :data="realisasis?.data || []" :pagination="realisasis" :selected="selected" :columns="columns" @select="onSelect" @action="handleAction" @paginate="handlePagination" @add="goToAdd" />

      <StatusLegend entity="Realisasi" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import StatusLegend from '@/components/ui/StatusLegend.vue';
import RealisasiFilter from '@/components/realisasi/RealisasiFilter.vue';
import RealisasiTable from '@/components/realisasi/RealisasiTable.vue';
import { Grid2x2Check, Send } from 'lucide-vue-next';
import { useMessagePanel } from '@/composables/useMessagePanel';

defineOptions({ layout: AppLayout });

interface Column { key: string; label: string; checked: boolean; sortable?: boolean }

const props = defineProps<{ realisasis: any; filters: Record<string, any>; departments: any[]; columns: Column[] }>();

const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi' }];

const realisasis = ref(props.realisasis || { data: [], total: 0, current_page: 1, last_page: 1 });
const currentFilters = ref(props.filters || {});
const columns = ref<Column[]>(props.columns || [
  { key: 'no_realisasi', label: 'No. Realisasi', checked: true, sortable: true },
  { key: 'no_po_anggaran', label: 'No. PO Anggaran', checked: true },
  { key: 'tanggal', label: 'Tanggal', checked: true, sortable: true },
  { key: 'department', label: 'Departemen', checked: true },
  { key: 'status', label: 'Status', checked: true, sortable: true },
]);
const departments = ref(props.departments || []);
const selected = ref<number[]>([]);

// Global message panel integration
const page = usePage();
const { addSuccess, addError } = useMessagePanel();

watch(
  () => page.props,
  (newProps: any) => {
    const flash = newProps?.flash || {};
    if (typeof flash.success === 'string' && flash.success) addSuccess(flash.success);
    if (typeof flash.error === 'string' && flash.error) addError(flash.error);
  },
  { immediate: true }
);

const canSendSelected = computed(() =>
  selected.value.length > 0 &&
  (realisasis.value?.data || [])
    .filter((r: any) => selected.value.includes(r.id))
    .every((row: any) => row.status === 'Draft' || row.status === 'Rejected')
);

async function loadRealisasis(params: Record<string, any> = {}) {
  const query = new URLSearchParams();
  Object.entries(currentFilters.value).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') query.set(k, String(v)); });
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') query.set(k, String(v)); });
  const response = await axios.get(`/realisasi?${query.toString()}`, { headers: { Accept: 'application/json' } });
  if (typeof response.data !== 'string') realisasis.value = response.data;
}

function applyFilters(payload: Record<string, any>) {
  currentFilters.value = { ...currentFilters.value, ...payload, per_page: payload.entriesPerPage || currentFilters.value.per_page || 10 };
  loadRealisasis();
}

function resetFilters() {
  currentFilters.value = { per_page: 10 };
  loadRealisasis();
}

function updateColumns(newColumns: Column[]) {
  columns.value = newColumns; currentFilters.value.columns = JSON.stringify(newColumns); loadRealisasis();
}

function updateEntriesPerPage(newPerPage: number) { currentFilters.value.per_page = newPerPage; loadRealisasis(); }

function handlePagination(url: string) { if (!url) return; const urlParams = new URLSearchParams(url.split('?')[1]); const page = urlParams.get('page'); if (page) { currentFilters.value.page = page; loadRealisasis(); } }

function onSelect(newSelected: number[]) { selected.value = newSelected; }

function handleAction(payload: { action: string; row: any }) {
  const { action, row } = payload;
  if (action === 'edit') router.visit(`/realisasi/${row.id}/edit`);
  if (action === 'delete') {
    router.delete(`/realisasi/${row.id}`, {
      onSuccess: () => addSuccess('Realisasi berhasil dihapus'),
      onError: () => addError('Terjadi kesalahan saat menghapus Realisasi'),
    });
  }
  if (action === 'detail') router.visit(`/realisasi/${row.id}`);
  if (action === 'log') router.visit(`/realisasi/${row.id}/log`);
  if (action === 'download') window.open(`/realisasi/${row.id}/download`, '_blank');
}

const showConfirmSend = ref(false);
function openConfirmSend() { if (!canSendSelected.value) return; showConfirmSend.value = true; }
function confirmSend() {
  router.post('/realisasi/send', { ids: selected.value }, {
    onSuccess: () => {
      selected.value = [];
      loadRealisasis();
    },
    onError: () => {
      addError('Terjadi kesalahan saat mengirim Realisasi');
    },
    preserveScroll: true,
  });
  showConfirmSend.value = false;
}
function cancelSend() { showConfirmSend.value = false; }

function goToAdd() { router.visit('/realisasi/create'); }
</script>
