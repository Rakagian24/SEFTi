<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Manage Purchase Order data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
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

      <PurchaseOrderFilter
        :filters="filters"
        :departments="departments"
        :perihals="perihals"
        :columns="columns"
        :entries-per-page="filters.per_page || 10"
        @filter="applyFilters"
        @reset="resetFilters"
        @update:columns="updateColumns"
        @update:entries-per-page="updateEntriesPerPage"
      />

      <PurchaseOrderTable
        :data="props.purchaseOrders?.data || []"
        :pagination="props.purchaseOrders"
        :selected="selected"
        :columns="columns"
        @select="onSelect"
        @action="handleAction"
        @paginate="handlePagination"
        @add="goToAdd"
      />

      <!-- Confirm Delete Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="confirmRow ? `Apakah Anda yakin ingin menghapus Purchase Order ini?` : ''"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderTable from "../../components/purchase-orders/PurchaseOrderTable.vue";
import PurchaseOrderFilter from "../../components/purchase-orders/PurchaseOrderFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { CreditCard, Send } from "lucide-vue-next";

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Purchase Order" }];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const props = defineProps<{
  purchaseOrders: any,
  filters: Record<string, any>,
  departments: any[],
  perihals: any[],
  columns?: Column[]
}>();

const departments = ref(props.departments || []);
const perihals = ref(props.perihals || []);
const selected = ref<number[]>([]);
const canSend = computed(() => selected.value.length > 0);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

// Default columns configuration
const defaultColumns: Column[] = [
  { key: "no_po", label: "No. PO", checked: true, sortable: true },
  { key: "no_invoice", label: "No. Invoice", checked: false, sortable: true },
  { key: "tipe_po", label: "Tipe PO", checked: true, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "department", label: "Departemen", checked: true, sortable: false },
  { key: "perihal", label: "Perihal", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: false, sortable: false },
  { key: "metode_pembayaran", label: "Metode Pembayaran", checked: false, sortable: false },
  { key: "total", label: "Total", checked: true, sortable: true },
  { key: "diskon", label: "Diskon", checked: false, sortable: true },
  { key: "ppn", label: "PPN", checked: false, sortable: true },
  { key: "pph", label: "PPH", checked: false, sortable: true },
  { key: "grand_total", label: "Grand Total", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
];

const columns = ref<Column[]>(props.columns || defaultColumns);

onMounted(() => {
  // Initialize columns from props or use defaults
  if (props.columns && props.columns.length > 0) {
    columns.value = props.columns;
  } else {
    columns.value = defaultColumns;
  }
});

function applyFilters(payload: Record<string, any>) {
  const params: Record<string, any> = {};
  if (payload.tanggal_start) params.tanggal_start = payload.tanggal_start;
  if (payload.tanggal_end) params.tanggal_end = payload.tanggal_end;
  if (payload.no_po) params.no_po = payload.no_po;
  if (payload.department_id) params.department_id = payload.department_id;
  if (payload.status) params.status = payload.status;
  if (payload.perihal_id) params.perihal_id = payload.perihal_id;
  if (payload.metode_pembayaran) params.metode_pembayaran = payload.metode_pembayaran;
  if (payload.entriesPerPage) params.per_page = payload.entriesPerPage;
  if (columns.value) params.columns = JSON.stringify(columns.value);

  router.get('/purchase-orders', params, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
  // Reset columns to defaults when resetting filters
  columns.value = [...defaultColumns];
  router.get('/purchase-orders', { per_page: 10, columns: JSON.stringify(columns.value) }, { preserveState: true });
}

function updateColumns(newColumns: Column[]) {
  columns.value = newColumns;
  // Apply filters with updated columns
  const currentFilters = { ...props.filters, columns: JSON.stringify(newColumns) };
  router.get('/purchase-orders', currentFilters, { preserveState: true, preserveScroll: true });
}

function updateEntriesPerPage(newPerPage: number) {
  const currentFilters = { ...props.filters, per_page: newPerPage };
  router.get('/purchase-orders', currentFilters, { preserveState: true, preserveScroll: true });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  const params: Record<string, any> = { ...props.filters, page };
  if (columns.value) params.columns = JSON.stringify(columns.value);
  router.get('/purchase-orders', params, { preserveState: true, preserveScroll: true });
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleAction(payload: { action: string, row: any }) {
  const { action, row } = payload;
  if (action === 'edit') router.visit(`/purchase-orders/${row.id}/edit`);
  if (action === 'delete') {
    confirmRow.value = row;
    showConfirmDialog.value = true;
  }
  if (action === 'detail') router.visit(`/purchase-orders/${row.id}`);
  if (action === 'log') router.visit(`/purchase-orders/${row.id}/log`);
  if (action === 'download') window.open(`/purchase-orders/${row.id}/download`, '_blank');
}

function confirmDelete() {
  if (confirmRow.value) {
    router.delete(`/purchase-orders/${confirmRow.value.id}`, {
      onSuccess: () => addSuccess('Purchase Order berhasil dibatalkan')
    });
  }
  cancelDelete();
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function sendSelected() {
  if (!canSend.value) return;
  router.post('/purchase-orders/send', { ids: selected.value }, {
    onSuccess: () => {
      addSuccess(`${selected.value.length} Purchase Order berhasil dikirim`)
      // Reload data so freshly generated no_po appears in the table
      router.reload({ only: ['purchaseOrders'] })
      selected.value = []
    },
    onError: () => addError('Terjadi kesalahan saat mengirim Purchase Order'),
    preserveScroll: true,
  });
}

function goToAdd() {
  router.visit('/purchase-orders/create');
}
</script>
