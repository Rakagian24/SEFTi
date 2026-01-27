<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-6 hidden items-center justify-between md:flex">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Realisasi</h1>
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <Grid2x2Check class="mr-1 h-4 w-4" />
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

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">Realisasi</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <Grid2x2Check class="mr-1 h-3 w-3" />
          Kelola Realisasi
        </div>
      </div>

      <!-- Mobile actions: Kirim + Add New -->
      <div class="mb-4 flex items-center justify-between gap-2 md:hidden">
        <div class="text-xs text-gray-600">
          <span v-if="selected.length > 0" class="font-semibold text-blue-600">
            {{ selected.length }}
          </span>
          <span v-else class="text-gray-400">0</span>
          dokumen dipilih
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openConfirmSend"
            :disabled="!canSendSelected"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              canSendSelected
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span>Kirim</span>
          </button>

          <button
            type="button"
            @click="goToAdd"
            class="inline-flex items-center gap-1 rounded-lg bg-[#101010] px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white hover:text-[#101010]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span>Add New</span>
          </button>
        </div>
      </div>

      <!-- Desktop / Tablet: Filters + Table -->
      <div class="hidden md:block">
        <RealisasiFilter :filters="filters" :departments="departments" :columns="columns" :entries-per-page="filters.per_page || 10" @filter="applyFilters" @reset="resetFilters" @update:columns="updateColumns" @update:entries-per-page="updateEntriesPerPage" />

        <RealisasiTable :data="realisasis?.data || []" :pagination="realisasis" :selected="selected" :columns="columns" @select="onSelect" @action="handleAction" @paginate="handlePagination" @add="goToAdd" />
      </div>

      <!-- Mobile: Card list -->
      <div class="mt-4 md:hidden">
        <!-- Simple search -->
        <div class="mb-4">
          <input
            v-model="currentFilters.search"
            type="text"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="Cari No. Realisasi / No. PO Anggaran / Departemen"
            @keyup.enter="loadRealisasis({ page: 1 })"
            @blur="loadRealisasis({ page: 1 })"
          />
        </div>

        <div
          v-if="realisasiRows.length === 0"
          class="py-8 text-center text-sm text-gray-500"
        >
          Belum ada Realisasi yang terdaftar.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="row in realisasiRows"
            :key="row.id"
            class="w-full rounded-xl bg-white p-3 text-left shadow-sm active:bg-slate-50"
          >
            <div class="mb-1 flex items-start justify-between">
              <div class="flex items-center gap-2">
                <input
                  v-if="canSendRow(row)"
                  type="checkbox"
                  :value="row.id"
                  v-model="selected"
                  class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500 self-center"
                  @click.stop
                />
                <div>
                  <div class="text-xs font-semibold text-gray-500">No. Realisasi</div>
                  <div class="text-xs font-semibold text-gray-900">
                    {{ row.no_realisasi || '-' }}
                  </div>
                </div>
              </div>

              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="getStatusBadgeClassMobile(row.status)"
              >
                {{ row.status || '-' }}
              </span>
            </div>

            <div class="mt-1 text-xs text-gray-500 truncate">
              {{ row.po_anggaran?.no_po_anggaran || '-' }}
            </div>

            <div class="mt-2 flex items-end justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="text-[11px] text-gray-500">Departemen</div>
                <div class="truncate text-xs font-medium text-gray-900">
                  {{ row.department?.name || '-' }}
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-gray-500">Total Realisasi</div>
                <div class="text-sm font-semibold text-emerald-700">
                  {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(row.total_realisasi || 0) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-400">
                  {{ row.tanggal ? new Date(row.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                </div>
              </div>
            </div>

            <!-- Mobile card actions menu -->
            <div class="mt-2 flex justify-end">
              <div class="relative inline-block text-left">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                  @click.stop="toggleMobileMenu(row.id)"
                >
                  <span class="sr-only">Buka menu</span>
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"
                    />
                  </svg>
                </button>

                <div
                  v-if="mobileMenuRealisasiId === row.id"
                  class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-lg bg-white py-1 text-xs shadow-lg ring-1 ring-black/5"
                >
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('detail', row)"
                  >
                    Detail
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('download', row)"
                  >
                    Download
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('log', row)"
                  >
                    Log
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canEditRowMobile(row)"
                    @click.stop="handleMobileAction('edit', row)"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canCloseRowMobile(row)"
                    @click.stop="handleMobileAction('close', row)"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div
          v-if="realisasiPage && realisasiRows.length > 0"
          class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
        >
          <nav
            class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
            aria-label="Pagination"
          >
            <button
              type="button"
              @click="handleMobilePaginate(realisasiPage.prev_page_url as any)"
              :disabled="!realisasiPage.prev_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                realisasiPage.prev_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Prev
            </button>

            <div class="px-3 py-1 text-[11px] text-gray-500">
              Halaman
              <span class="font-semibold text-gray-800">{{ (realisasiPage.current_page as any) || 1 }}</span>
            </div>

            <button
              type="button"
              @click="handleMobilePaginate(realisasiPage.next_page_url as any)"
              :disabled="!realisasiPage.next_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                realisasiPage.next_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Next
            </button>
          </nav>
        </div>
      </div>

      <StatusLegend entity="Realisasi" />

      <!-- Close Reason Dialog -->
      <CloseReasonDialog
        :is-open="showCloseReasonDialog"
        @update:open="(val: boolean) => (showCloseReasonDialog = val)"
        @cancel="cancelClose"
        @confirm="confirmClose"
      />
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
import CloseReasonDialog from '@/components/approval/CloseReasonDialog.vue';
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
  { key: 'metode_pembayaran', label: 'Metode Pembayaran', checked: true },
  { key: 'bisnis_partner', label: 'Bisnis Partner', checked: true },
  { key: 'total_anggaran', label: 'Total Anggaran', checked: true, sortable: true },
  { key: 'total_realisasi', label: 'Total Realisasi', checked: true, sortable: true },
  { key: 'sisa', label: 'Sisa', checked: true },
  { key: 'status', label: 'Status', checked: true, sortable: true },
]);
const departments = ref(props.departments || []);
const selected = ref<number[]>([]);
const showCloseReasonDialog = ref(false);
const closeRow = ref<any | null>(null);
const mobileMenuRealisasiId = ref<number | null>(null);

const realisasiRows = computed<any[]>(() => (realisasis.value?.data || []) as any[]);
const realisasiPage = computed<any | null>(() => realisasis.value || null);

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
    .every((row: any) => (row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin.value))
);

// Permissions like PO Anggaran: only creator or Admin can send Draft/Rejected
const currentUserId = computed<string | number | null>(() => (usePage().props as any)?.auth?.user?.id ?? null);
const isAdmin = computed<boolean>(() => ((usePage().props as any)?.auth?.user?.role?.name) === 'Admin');
function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.created_by ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function canSendRow(row: any) {
  return (row.status === 'Draft' || row.status === 'Rejected') && (isCreatorRow(row) || isAdmin.value);
}

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
      onSuccess: () => addSuccess('Realisasi berhasil dibatalkan'),
      onError: () => addError('Terjadi kesalahan saat menghapus Realisasi'),
    });
  }
  if (action === 'detail') router.visit(`/realisasi/${row.id}`);
  if (action === 'log') router.visit(`/realisasi/${row.id}/log`);
  if (action === 'download') window.open(`/realisasi/${row.id}/download`, '_blank');
  if (action === 'close') {
    closeRow.value = row;
    showCloseReasonDialog.value = true;
  }
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

function confirmClose(reason: string) {
  if (!closeRow.value) return;

  router.post(
    `/realisasi/${closeRow.value.id}/close`,
    { reason },
    {
      onSuccess: () => {
        loadRealisasis();
      },
      onError: () => addError('Terjadi kesalahan saat menutup Realisasi'),
      preserveScroll: true,
    }
  );
  cancelClose();
}

function cancelClose() {
  showCloseReasonDialog.value = false;
  closeRow.value = null;
}

function toggleMobileMenu(realisasiId: number) {
  mobileMenuRealisasiId.value = mobileMenuRealisasiId.value === realisasiId ? null : realisasiId;
}

function handleMobileAction(action: 'detail' | 'download' | 'log' | 'edit' | 'close', row: any) {
  mobileMenuRealisasiId.value = null;
  if (!row?.id) return;

  if (action === 'detail') {
    router.visit(`/realisasi/${row.id}`);
    return;
  }
  if (action === 'download') {
    window.open(`/realisasi/${row.id}/download`, '_blank');
    return;
  }
  if (action === 'log') {
    router.visit(`/realisasi/${row.id}/log`);
    return;
  }
  if (action === 'edit') {
    router.visit(`/realisasi/${row.id}/edit`);
    return;
  }
  if (action === 'close') {
    closeRow.value = row;
    showCloseReasonDialog.value = true;
  }
}

// Mobile helpers to mirror desktop action visibility rules
function canEditRowMobile(row: any) {
  if (!row) return false;
  if (row.status === 'Draft') {
    return isCreatorRow(row);
  }
  if (row.status === 'Rejected') {
    return isCreatorRow(row) || isAdmin.value;
  }
  return false;
}

function canCloseRowMobile(row: any) {
  if (!row) return false;
  return row.status === 'Approved';
}

function getStatusBadgeClassMobile(status: string) {
  return getSharedStatusBadgeClass(status || 'Draft');
}

function handleMobilePaginate(url: string | null) {
  if (!url) return;
  handlePagination(url);
}

// Import status helper
import { getStatusBadgeClass as getSharedStatusBadgeClass } from '@/lib/status';
</script>
