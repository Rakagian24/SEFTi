<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ListBayarFilter from '@/components/list-bayar/ListBayarFilter.vue';
import ListBayarTable from '@/components/list-bayar/ListBayarTable.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { getIconForPage } from '@/lib/iconMapping';
import { useAlertDialog } from '@/composables/useAlertDialog';

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'List Bayar' }
];

const { showWarning } = useAlertDialog();

const props = defineProps({
  list: Object,
  filters: Object,
  supplierOptions: Array,
  departmentOptions: Array,
  exportEnabled: Boolean,
});

const list = computed(() => props.list);
const filters = computed(() => props.filters as any || {});
const supplierOptions = computed(() => Array.isArray(props.supplierOptions) ? props.supplierOptions : []);
const departmentOptions = computed(() => Array.isArray(props.departmentOptions) ? props.departmentOptions : []);
const exportEnabled = computed(() => props.exportEnabled === true);

const entriesPerPage = ref(props.filters?.per_page || 10);

function handleFilterChange(newFilters: any) {
  const filterParams = {
    ...filters.value,
    ...newFilters,
    per_page: newFilters.per_page || entriesPerPage.value,
    page: 1,
    tanggal_start: newFilters.tanggal_start || filters.value.tanggal_start,
    tanggal_end: newFilters.tanggal_end || filters.value.tanggal_end,
    supplier_id: newFilters.supplier_id || filters.value.supplier_id,
    department_id: newFilters.department_id || filters.value.department_id,
  };

  router.get('/list-bayar', filterParams, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
  });
}

function handlePaginate(url: any) {
  const urlObj = new URL(url, window.location.origin);
  const params = Object.fromEntries(urlObj.searchParams.entries());
  router.get('/list-bayar', {
    ...filters.value,
    ...params,
    per_page: params.per_page || entriesPerPage.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handleResetFilters() {
  entriesPerPage.value = 10;

  const resetParams = {
    per_page: 10,
    page: 1,
  };

  router.get('/list-bayar', resetParams, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
  });
}

function exportPdf() {
  if (!filters.value.tanggal_start || !filters.value.tanggal_end) {
    showWarning('Silakan pilih rentang tanggal terlebih dahulu untuk export PDF!', 'Peringatan Export');
    return;
  }

  const params = new URLSearchParams({
    tanggal_start: filters.value.tanggal_start,
    tanggal_end: filters.value.tanggal_end,
  });

  if (filters.value.supplier_id) {
    params.append('supplier_id', String(filters.value.supplier_id));
  }
  if (filters.value.department_id) {
    params.append('department_id', String(filters.value.department_id));
  }

  window.location.href = `/list-bayar/export-pdf?${params.toString()}`;
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Head title="List Bayar" />

      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">List Bayar</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <component :is="getIconForPage('List Bayar')" class="w-4 h-4 mr-1" />
            Kelola daftar pembayaran
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Export to PDF Button -->
          <button
            @click="exportPdf"
            :disabled="!exportEnabled || !filters.tanggal_start || !filters.tanggal_end"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent hover:border-[#101010]"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
              />
            </svg>
            Export to PDF
          </button>
        </div>
      </div>

      <!-- Filter Section -->
      <ListBayarFilter
        :filters="filters"
        :supplierOptions="supplierOptions"
        :departmentOptions="departmentOptions"
        v-model:entries-per-page="entriesPerPage"
        @change="handleFilterChange"
        @reset="handleResetFilters"
      />

      <!-- Table Section -->
      <ListBayarTable
        :list="list"
        @paginate="handlePaginate"
      />
    </div>
  </div>
</template>
