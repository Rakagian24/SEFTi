<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import StockMutationFilter from '@/components/stock-mutation/StockMutationFilter.vue';
import StockMutationTable from '@/components/stock-mutation/StockMutationTable.vue';
import { ArrowLeftRight } from 'lucide-vue-next';

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Mutasi Stock' },
];

defineOptions({ layout: AppLayout });

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
      (r.jenis || '').toString().toLowerCase().includes(term)
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

    const { data } = await axios.get('/mutasi-stock/data', {
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

async function exportExcel() {
  if (!filters.value.department_id || totalRows.value === 0) return;

  const params: Record<string, any> = {
    department_id: filters.value.department_id,
  };
  if (filters.value.tanggal_start) params.tanggal_start = filters.value.tanggal_start;
  if (filters.value.tanggal_end) params.tanggal_end = filters.value.tanggal_end;

  const query = new URLSearchParams(params as any).toString();
  const res = await fetch(`/mutasi-stock/export-excel?${query}`, {
    method: 'GET',
    headers: { 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
    credentials: 'same-origin',
  });

  if (!res.ok) return;

  const blob = await res.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(url);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Mutasi Stock</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <ArrowLeftRight class="w-4 h-4 mr-1" />
            Rekap saldo awal, masuk, keluar, dan saldo akhir per barang
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
            :disabled="loading || totalRows === 0"
            @click="exportExcel"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0,0,256,256" fill="currentColor">
              <g fill="currentColor" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                <g transform="scale(5.12,5.12)">
                  <path d="M28.875,0c-0.01953,0.00781 -0.04297,0.01953 -0.0625,0.03125l-28,5.3125c-0.47656,0.08984 -0.82031,0.51172 -0.8125,1v37.3125c-0.00781,0.48828 0.33594,0.91016 0.8125,1l28,5.3125c0.28906,0.05469 0.58984,-0.01953 0.82031,-0.20703c0.22656,-0.1875 0.36328,-0.46484 0.36719,-0.76172v-5h17c1.09375,0 2,-0.90625 2,-2v-34c0,-1.09375 -0.90625,-2 -2,-2h-17v-5c0.00391,-0.28906 -0.12109,-0.5625 -0.33594,-0.75391c-0.21484,-0.19141 -0.50391,-0.28125 -0.78906,-0.24609zM28,2.1875v4.34375c-0.13281,0.27734 -0.13281,0.59766 0,0.875v35.40625c-0.02734,0.13281 -0.02734,0.27344 0,0.40625v4.59375l-26,-4.96875v-35.6875zM30,8h17v34h-17v-5h4v-2h-4v-6h4v-2h-4v-5h4v-2h-4v-5h4v-2h-4zM36,13v2h8v-2zM6.6875,15.6875l5.46875,9.34375l-5.96875,9.34375h5l3.25,-6.03125c0.22656,-0.58203 0.375,-1.02734 0.4375,-1.3125h0.03125c0.12891,0.60938 0.25391,1.02344 0.375,1.25l3.25,6.09375h4.96875l-5.75,-9.4375l5.59375,-9.25h-4.6875l-2.96875,5.53125c-0.28516,0.72266 -0.48828,1.29297 -0.59375,1.65625h-0.03125c-0.16406,-0.60937 -0.35156,-1.15234 -0.5625,-1.59375l-2.6875,-5.59375zM36,20v2h8v-2zM36,27v2h8v-2zM36,35v2h8v-2z"></path>
                </g>
              </g>
            </svg>
            Export to Excel
          </button>
        </div>
      </div>

      <StockMutationFilter
        :departments="departments"
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @reset="handleReset"
        @update:per-page="handleChangePageSize"
        @update:search="(v: string) => (filters.search = v)"
      />

      <StockMutationTable
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
