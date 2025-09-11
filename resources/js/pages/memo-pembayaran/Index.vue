<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Memo Pembayaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Manage Memo Pembayaran data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div v-if="selected.length > 0" class="flex items-center gap-2">
            <button
              @click="sendSelected"
              :disabled="!canSend"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="w-4 h-4" />
              Kirim ({{ selected.length }})
            </button>
          </div>

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

      <MemoPembayaranFilter
        :filters="filters"
        :departments="departments"
        :statusOptions="statusOptions"
        :metodePembayaranOptions="metodePembayaranOptions"
        :columns="columns"
        @filter="applyFilters"
        @reset="resetFilters"
        @update:columns="updateColumns"
      />

      <MemoPembayaranTable
        :data="props.memoPembayarans?.data || []"
        :pagination="props.memoPembayarans"
        :selected="selected"
        :columns="columns"
        @select="onSelect"
        @action="handleAction"
        @paginate="handlePagination"
        @add="goToAdd"
        @update:columns="updateColumns"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import MemoPembayaranTable from "../../components/memo-pembayaran/MemoPembayaranTable.vue";
import MemoPembayaranFilter from "../../components/memo-pembayaran/MemoPembayaranFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { WalletCards, Send } from "lucide-vue-next";

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Memo Pembayaran" }];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const props = defineProps<{
  memoPembayarans: any,
  filters: Record<string, any>,
  departments: any[],
  statusOptions: string[],
  metodePembayaranOptions: string[]
}>();
const departments = ref(props.departments || []);
const statusOptions = ref(props.statusOptions || []);
const metodePembayaranOptions = ref(props.metodePembayaranOptions || []);
const selected = ref<number[]>([]);
const canSend = computed(() => selected.value.length > 0);

// Columns configuration - Default columns for regular memo pembayaran view
const columns = ref([
  { key: "no_mb", label: "No. MB", checked: true, sortable: false },
  { key: "no_po", label: "No. PO", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: true, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  { key: "perihal", label: "Perihal", checked: false, sortable: false },
  { key: "department", label: "Department", checked: false, sortable: false },
  { key: "detail_keperluan", label: "Detail Keperluan", checked: false, sortable: false },
  { key: "metode_pembayaran", label: "Metode Pembayaran", checked: false, sortable: false },
  { key: "grand_total", label: "Grand Total", checked: false, sortable: true },
  { key: "nama_rekening", label: "Nama Rekening", checked: false, sortable: false },
  { key: "no_rekening", label: "No. Rekening", checked: false, sortable: false },
  { key: "no_kartu_kredit", label: "No. Kartu Kredit", checked: false, sortable: false },
  { key: "no_giro", label: "No. Giro", checked: false, sortable: false },
  { key: "tanggal_giro", label: "Tanggal Giro", checked: false, sortable: true },
  { key: "tanggal_cair", label: "Tanggal Cair", checked: false, sortable: true },
  { key: "keterangan", label: "Keterangan", checked: false, sortable: false },
  { key: "total", label: "Total", checked: false, sortable: true },
  { key: "diskon", label: "Diskon", checked: false, sortable: true },
  { key: "ppn", label: "PPN", checked: false, sortable: false },
  { key: "ppn_nominal", label: "PPN Nominal", checked: false, sortable: true },
  { key: "pph_nominal", label: "PPH Nominal", checked: false, sortable: true },
  { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
]);

function applyFilters(payload: Record<string, any>) {
  const params: Record<string, any> = {};
  if (payload.tanggal_start) params.tanggal_start = payload.tanggal_start;
  if (payload.tanggal_end) params.tanggal_end = payload.tanggal_end;
  if (payload.no_mb) params.no_mb = payload.no_mb;
  if (payload.department_id) params.department_id = payload.department_id;
  if (payload.status) params.status = payload.status;
  if (payload.metode_pembayaran) params.metode_pembayaran = payload.metode_pembayaran;
  if (payload.search) params.search = payload.search;
  if (payload.entriesPerPage) params.per_page = payload.entriesPerPage;
  if (payload.search_columns) params.search_columns = payload.search_columns;

  router.get('/memo-pembayaran', params, { preserveState: true, preserveScroll: true });
}

function updateColumns(newColumns: any[]) {
  columns.value = newColumns;
}

function resetFilters() {
  router.get('/memo-pembayaran', { per_page: 10 }, { preserveState: true });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  router.get('/memo-pembayaran', { ...props.filters, page }, { preserveState: true, preserveScroll: true });
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleAction(payload: { action: string, row: any }) {
  const { action, row } = payload;
  if (action === 'edit') router.visit(`/memo-pembayaran/${row.id}/edit`);
  if (action === 'delete') router.delete(`/memo-pembayaran/${row.id}`, { onSuccess: () => addSuccess('Memo Pembayaran berhasil dibatalkan') });
  if (action === 'detail') router.visit(`/memo-pembayaran/${row.id}`);
  if (action === 'log') router.visit(`/memo-pembayaran/${row.id}/log`);
  if (action === 'download') window.open(`/memo-pembayaran/${row.id}/download`, '_blank');
}

function sendSelected() {
  if (!canSend.value) return;
  router.post('/memo-pembayaran/send', { ids: selected.value }, {
    onSuccess: () => addSuccess(`${selected.value.length} Memo Pembayaran berhasil dikirim`),
    onError: () => addError('Terjadi kesalahan saat mengirim Memo Pembayaran'),
    preserveScroll: true,
    });
}

function goToAdd() {
  router.visit('/memo-pembayaran/create');
}
</script>
