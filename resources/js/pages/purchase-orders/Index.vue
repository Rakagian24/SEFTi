<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <ShoppingCart class="w-4 h-4 mr-1" />
            Manage Purchase Order data
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

      <PurchaseOrderFilter
        :filters="filters"
        :departments="departments"
        :perihals="perihals"
        @filter="applyFilters"
        @reset="resetFilters"
      />

      <PurchaseOrderTable
        :data="props.purchaseOrders?.data || []"
        :pagination="props.purchaseOrders"
        :selected="selected"
        @select="onSelect"
        @action="handleAction"
        @paginate="handlePagination"
        @add="goToAdd"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderTable from "../../components/purchase-orders/PurchaseOrderTable.vue";
import PurchaseOrderFilter from "../../components/purchase-orders/PurchaseOrderFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { ShoppingCart, Send } from "lucide-vue-next";

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Purchase Order" }];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const props = defineProps<{ purchaseOrders: any, filters: Record<string, any>, departments: any[], perihals: any[] }>();
const departments = ref(props.departments || []);
const perihals = ref(props.perihals || []);
const selected = ref<number[]>([]);
const canSend = computed(() => selected.value.length > 0);

function applyFilters(payload: Record<string, any>) {
  const params: Record<string, any> = {};
  if (payload.tanggal_start) params.tanggal_start = payload.tanggal_start;
  if (payload.tanggal_end) params.tanggal_end = payload.tanggal_end;
  if (payload.no_po) params.no_po = payload.no_po;
  if (payload.department_id) params.department_id = payload.department_id;
  if (payload.status) params.status = payload.status;
  if (payload.perihal) params.perihal = payload.perihal;
  if (payload.metode_pembayaran) params.metode_pembayaran = payload.metode_pembayaran;
  if (payload.entriesPerPage) params.per_page = payload.entriesPerPage;

  router.get('/purchase-orders', params, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
  router.get('/purchase-orders', { per_page: 10 }, { preserveState: true });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  router.get('/purchase-orders', { ...props.filters, page }, { preserveState: true, preserveScroll: true });
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleAction(payload: { action: string, row: any }) {
  const { action, row } = payload;
  if (action === 'edit') router.visit(`/purchase-orders/${row.id}/edit`);
  if (action === 'delete') router.delete(`/purchase-orders/${row.id}`, { onSuccess: () => addSuccess('Purchase Order berhasil dibatalkan') });
  if (action === 'detail') router.visit(`/purchase-orders/${row.id}`);
  if (action === 'log') router.visit(`/purchase-orders/${row.id}/log`);
  if (action === 'download') window.open(`/purchase-orders/${row.id}/download`, '_blank');
}

function sendSelected() {
  if (!canSend.value) return;
  router.post('/purchase-orders/send', { ids: selected.value }, {
    onSuccess: () => addSuccess(`${selected.value.length} Purchase Order berhasil dikirim`),
    onError: () => addError('Terjadi kesalahan saat mengirim Purchase Order'),
    preserveScroll: true,
    });
}

function goToAdd() {
  router.visit('/purchase-orders/create');
}
</script>
