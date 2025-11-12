<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">PO Anggaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Manage PO Anggaran data
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

          <ConfirmDialog :show="showConfirmSend" :message="`Apakah Anda yakin ingin mengirim ${selected.length} PO Anggaran?`" @confirm="confirmSend" @cancel="cancelSend" />

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

      <PoAnggaranFilter :filters="filters" :departments="departments" :columns="columns" :entries-per-page="filters.per_page || 10" @filter="applyFilters" @reset="resetFilters" @update:columns="updateColumns" @update:entriesPerPage="updateEntriesPerPage" />

      <PoAnggaranTable :data="poAnggarans?.data || []" :pagination="poAnggarans" :selected="selected" :columns="columns" @select="onSelect" @action="handleAction" @paginate="handlePagination" @add="goToAdd" />

      <StatusLegend entity="PO Anggaran" />

      <ConfirmDialog :show="showConfirmDialog" :message="confirmRow ? `Apakah Anda yakin ingin membatalkan PO Anggaran ini?` : ''" @confirm="confirmDelete" @cancel="cancelDelete" />
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
import PoAnggaranFilter from '@/components/po-anggaran/PoAnggaranFilter.vue';
import PoAnggaranTable from '@/components/po-anggaran/PoAnggaranTable.vue';
import { CreditCard, Send } from 'lucide-vue-next';
import { useMessagePanel } from '@/composables/useMessagePanel';

defineOptions({ layout: AppLayout });

interface Column { key: string; label: string; checked: boolean; sortable?: boolean }

const props = defineProps<{ poAnggarans: any; filters: Record<string, any>; departments: any[]; columns: Column[] }>();

const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'PO Anggaran' }];

const poAnggarans = ref(props.poAnggarans || { data: [], total: 0, current_page: 1, last_page: 1 });
const currentFilters = ref(props.filters || {});
const columns = ref<Column[]>(props.columns || [
  { key: 'no_po_anggaran', label: 'No. PO Anggaran', checked: true, sortable: true },
  { key: 'tanggal', label: 'Tanggal', checked: true, sortable: true },
  { key: 'department', label: 'Departemen', checked: true },
  { key: 'nominal', label: 'Nominal', checked: true, sortable: true },
  { key: 'status', label: 'Status', checked: true, sortable: true },
]);
const departments = ref(props.departments || []);
const selected = ref<number[]>([]);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

// Permissions like Purchase Order: only creator or Admin can send Draft/Rejected
const currentUserId = computed<string | number | null>(() => (usePage().props as any)?.auth?.user?.id ?? null);
const isAdmin = computed<boolean>(() => ((usePage().props as any)?.auth?.user?.role?.name) === 'Admin');
function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.created_by ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}
const canSendSelected = computed(() => {
  if (selected.value.length === 0) return false;
  const rows = (poAnggarans.value?.data || []).filter((r: any) => selected.value.includes(r.id));
  return rows.every((row: any) => (row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin.value));
});

async function loadPoAnggarans(params: Record<string, any> = {}) {
  const query = new URLSearchParams();
  Object.entries(currentFilters.value).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') query.set(k, String(v)); });
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') query.set(k, String(v)); });
  const response = await axios.get(`/po-anggaran?${query.toString()}`, { headers: { Accept: 'application/json' } });
  if (typeof response.data !== 'string') poAnggarans.value = response.data;
}

function applyFilters(payload: Record<string, any>) {
  currentFilters.value = { ...currentFilters.value, ...payload, per_page: payload.entriesPerPage || currentFilters.value.per_page || 10 };
  loadPoAnggarans();
}

function resetFilters() {
  currentFilters.value = { per_page: 10 };
  loadPoAnggarans();
}

function updateColumns(newColumns: Column[]) {
  columns.value = newColumns; currentFilters.value.columns = JSON.stringify(newColumns); loadPoAnggarans();
}

function updateEntriesPerPage(newPerPage: number) { currentFilters.value.per_page = newPerPage; loadPoAnggarans(); }

function handlePagination(url: string) { if (!url) return; const urlParams = new URLSearchParams(url.split('?')[1]); const page = urlParams.get('page'); if (page) { currentFilters.value.page = page; loadPoAnggarans(); } }

function onSelect(newSelected: number[]) { selected.value = newSelected; }

function handleAction(payload: { action: string; row: any }) {
  const { action, row } = payload;
  if (action == 'edit') {
    if (isCreatorRow(row) || isAdmin.value) router.visit(`/po-anggaran/${row.id}/edit`);
    else router.visit(`/po-anggaran/${row.id}`);
    return;
  }
  if (action == 'delete') { confirmRow.value = row; showConfirmDialog.value = true; }
  if (action == 'detail') router.visit(`/po-anggaran/${row.id}`);
  if (action == 'log') router.visit(`/po-anggaran/${row.id}/log`);
  if (action == 'download') window.open(`/po-anggaran/${row.id}/download`, '_blank');
}

const showConfirmSend = ref(false);
function openConfirmSend() { if (!canSendSelected.value) return; showConfirmSend.value = true; }
function confirmSend() {
  router.post('/po-anggaran/send', { ids: selected.value }, { onSuccess: () => { selected.value = []; loadPoAnggarans(); } });
  showConfirmSend.value = false;
}
function cancelSend() { showConfirmSend.value = false; }

function goToAdd() { router.visit('/po-anggaran/create'); }

function confirmDelete() {
  if (confirmRow.value) {
    router.delete(`/po-anggaran/${confirmRow.value.id}` , {
      onSuccess: () => {
        loadPoAnggarans();
      },
    });
  }
  cancelDelete();
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

// Message panel handling like other modules
const page = usePage();
const { addSuccess, addError } = useMessagePanel();
watch(
  () => page.props,
  (newProps) => {
    const flash = (newProps as any)?.flash || {};
    if (typeof flash.success === 'string' && flash.success) addSuccess(flash.success);
    if (typeof flash.error === 'string' && flash.error) addError(flash.error);
  },
  { immediate: true }
);
</script>
