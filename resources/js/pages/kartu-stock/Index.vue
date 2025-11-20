<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import StockCardFilter from '@/components/stock-card/StockCardFilter.vue';
import StockCardTable from '@/components/stock-card/StockCardTable.vue';
import { Layers3 } from 'lucide-vue-next';

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Kartu Stock' },
];

defineOptions({ layout: AppLayout });

const pageProps = defineProps<{
  departments: any[];
}>();

const departments = ref(pageProps.departments || []);

const filters = ref({
  department_id: '',
  item_name: '',
  tanggal_start: '',
  tanggal_end: '',
  per_page: 10,
  search: '',
});

const loading = ref(false);
const rows = ref<any[]>([]);
const saldoAwal = ref(0);
const saldoAkhir = ref(0);
const totalMasuk = ref(0);
const totalKeluar = ref(0);

const currentPage = ref(1);
const itemOptions = ref<{ label: string; value: string }[]>([]);

const filteredRows = computed(() => {
  const term = (filters.value.search || '').toString().toLowerCase().trim();
  if (!term) return rows.value;
  return rows.value.filter((r) => {
    return (
      (r.referensi || '').toString().toLowerCase().includes(term) ||
      (r.tanggal || '').toString().toLowerCase().includes(term)
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

async function loadItems() {
  if (!filters.value.department_id) {
    itemOptions.value = [];
    filters.value.item_name = '';
    return;
  }

  const { data } = await axios.get('/kartu-stock/items', {
    params: {
      department_id: filters.value.department_id,
    },
    headers: { Accept: 'application/json' },
  });

  itemOptions.value = Array.isArray(data.data) ? data.data : [];
}

async function loadData() {
  if (!filters.value.department_id || !filters.value.item_name) {
    rows.value = [];
    saldoAwal.value = 0;
    saldoAkhir.value = 0;
    totalMasuk.value = 0;
    totalKeluar.value = 0;
    currentPage.value = 1;
    return;
  }

  loading.value = true;
  try {
    const params: Record<string, any> = {
      department_id: filters.value.department_id,
      item_name: filters.value.item_name,
    };
    if (filters.value.tanggal_start) params.tanggal_start = filters.value.tanggal_start;
    if (filters.value.tanggal_end) params.tanggal_end = filters.value.tanggal_end;

    const { data } = await axios.get('/kartu-stock/data', {
      params,
      headers: { Accept: 'application/json' },
    });

    rows.value = Array.isArray(data.rows) ? data.rows : [];
    saldoAwal.value = Number(data.saldo_awal || 0);
    saldoAkhir.value = Number(data.saldo_akhir || 0);
    totalMasuk.value = Number(data.total_masuk || 0);
    totalKeluar.value = Number(data.total_keluar || 0);
    currentPage.value = 1;
  } finally {
    loading.value = false;
  }
}

function handleFilter(payload: any) {
  const prevDept = filters.value.department_id;

  filters.value = {
    ...filters.value,
    ...payload,
  };

  if (payload.department_id && payload.department_id !== prevDept) {
    loadItems();
  }

  currentPage.value = 1;
  loadData();
}

function handleReset() {
  filters.value = {
    department_id: '',
    item_name: '',
    tanggal_start: '',
    tanggal_end: '',
    per_page: 10,
    search: '',
  };
  rows.value = [];
  saldoAwal.value = 0;
  saldoAkhir.value = 0;
  totalMasuk.value = 0;
  totalKeluar.value = 0;
  currentPage.value = 1;
  itemOptions.value = [];
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
          <h1 class="text-2xl font-bold text-gray-900">Kartu Stock</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Layers3 class="w-4 h-4 mr-1" />
            Mutasi masuk / keluar dan saldo untuk satu barang per departemen
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

      <StockCardFilter
        :departments="departments"
        :item-options="itemOptions"
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @reset="handleReset"
        @update:per-page="handleChangePageSize"
        @update:search="(v: string) => (filters.search = v)"
      />

      <StockCardTable
        :rows="pagedRows"
        :loading="loading"
        :current-page="currentPage"
        :per-page="Number(filters.per_page)"
        :total="totalRows"
        :saldo-awal="saldoAwal"
        :saldo-akhir="saldoAkhir"
        :total-masuk="totalMasuk"
        :total-keluar="totalKeluar"
        @change-page="handleChangePage"
      />
    </div>
  </div>
</template>
