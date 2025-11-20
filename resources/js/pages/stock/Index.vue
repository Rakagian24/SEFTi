<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import StockFilter from '@/components/stock/StockFilter.vue';
import StockTable from '@/components/stock/StockTable.vue';
import { Boxes } from 'lucide-vue-next';

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Stock' },
];

defineOptions({ layout: AppLayout });

// Only use departments from props to avoid name collision with local filters state
const pageProps = defineProps<{
  departments: any[];
}>();

const departments = ref(pageProps.departments || []);

const filters = ref({
  tanggal_start: '',
  tanggal_end: '',
  department_id: '',
  per_page: 10,
  search: '',
});

const rows = ref<any[]>([]);
const loading = ref(false);
const currentPage = ref(1);

const filteredRows = computed(() => {
  const term = (filters.value.search || '').toString().toLowerCase().trim();
  if (!term) return rows.value;
  return rows.value.filter((r) => {
    return (
      (r.nama_barang || '').toString().toLowerCase().includes(term) ||
      (r.jenis || '').toString().toLowerCase().includes(term) ||
      (r.satuan || '').toString().toLowerCase().includes(term)
    );
  });
});

const pagedRows = computed(() => {
  const perPage = Number(filters.value.per_page) || 10;
  const start = (currentPage.value - 1) * perPage;
  return filteredRows.value.slice(start, start + perPage);
});

const totalRows = computed(() => filteredRows.value.length);
const lastPage = computed(() => {
  const perPage = Number(filters.value.per_page) || 10;
  if (perPage <= 0) return 1;
  return Math.max(1, Math.ceil(totalRows.value / perPage));
});

async function loadData() {
  if (!filters.value.department_id) {
    rows.value = [];
    currentPage.value = 1;
    return;
  }

  loading.value = true;
  try {
    const params: Record<string, any> = {
      department_id: filters.value.department_id,
    };
    if (filters.value.tanggal_start) params.tanggal_start = filters.value.tanggal_start;
    if (filters.value.tanggal_end) params.tanggal_end = filters.value.tanggal_end;

    const { data } = await axios.get('/stock/data', {
      params,
      headers: { Accept: 'application/json' },
    });

    rows.value = Array.isArray(data.data) ? data.data : [];
    currentPage.value = 1;
  } finally {
    loading.value = false;
  }
}

function handleFilter(payload: any) {
  filters.value = {
    ...filters.value,
    ...payload,
  };
  currentPage.value = 1;
  loadData();
}

function handleReset() {
  filters.value = {
    tanggal_start: '',
    tanggal_end: '',
    department_id: '',
    per_page: 10,
    search: '',
  };
  rows.value = [];
  currentPage.value = 1;
}

function handleChangePage(page: number) {
  if (page < 1 || page > lastPage.value) return;
  currentPage.value = page;
}

function handleChangePageSize(size: number) {
  filters.value.per_page = size;
  currentPage.value = 1;
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Laporan Stock</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Boxes class="w-4 h-4 mr-1" />
            Ringkasan stock berdasarkan BPB yang sudah Approved
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700 disabled:opacity-50"
            :disabled="loading || totalRows === 0"
          >
            Exp to Excel
          </button>
        </div>
      </div>

      <StockFilter
        :departments="departments"
        :loading="loading"
        :filters="filters"
        @filter="handleFilter"
        @reset="handleReset"
        @update:per-page="handleChangePageSize"
        @update:search="(v: string) => (filters.search = v)"
      />

      <StockTable
        :rows="pagedRows"
        :loading="loading"
        :current-page="currentPage"
        :per-page="Number(filters.per_page)"
        :total="totalRows"
        @change-page="handleChangePage"
      />
    </div>
  </div>
</template>
