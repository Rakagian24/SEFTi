<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">PO Outstanding</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <FileSpreadsheet class="w-4 h-4 mr-1" />
            Laporan daftar Purchase Order yang masih outstanding
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700 disabled:opacity-50"
            :disabled="isLoading || rows.length === 0"
            @click="exportExcel"
          >
            Exp to Excel
          </button>
          <button
            class="px-4 py-2 bg-rose-600 text-white text-sm rounded-md hover:bg-rose-700 disabled:opacity-50"
            :disabled="isLoading || rows.length === 0"
            @click="exportPdf"
          >
            Exp to PDF
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-lg shadow mb-4">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Tanggal Mulai</label>
            <input type="date" v-model="filters.tanggal_start" class="w-full border rounded px-2 py-1 text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Tanggal Akhir</label>
            <input type="date" v-model="filters.tanggal_end" class="w-full border rounded px-2 py-1 text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">No. PO</label>
            <input type="text" v-model="filters.no_po" placeholder="Cari No. PO" class="w-full border rounded px-2 py-1 text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Departemen</label>
            <select v-model="filters.department_id" class="w-full border rounded px-2 py-1 text-sm">
              <option value="">Semua</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Supplier</label>
            <input type="text" v-model="filters.supplier_name" placeholder="Nama Supplier" class="w-full border rounded px-2 py-1 text-sm" />
          </div>
          <div class="flex items-end gap-2">
            <button class="px-3 py-2 bg-[#101010] text-white rounded text-sm" @click="applyFilters">Filter</button>
            <button class="px-3 py-2 bg-gray-100 border rounded text-sm" @click="resetFilters">Reset</button>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow">
        <div class="flex items-center justify-between p-3 border-b">
          <div class="text-sm text-gray-600">Rows per page:</div>
          <select v-model.number="filters.per_page" class="border rounded px-2 py-1 text-sm" @change="loadData()">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
        <div class="overflow-auto">
          <table class="min-w-full">
            <thead>
              <tr class="bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-3 py-2"><input type="checkbox" :checked="allSelected" @change="toggleSelectAll" /></th>
                <th class="px-3 py-2">No. PO</th>
                <th class="px-3 py-2">Departemen</th>
                <th class="px-3 py-2">Tanggal</th>
                <th class="px-3 py-2">Perihal</th>
                <th class="px-3 py-2">Supplier</th>
                <th class="px-3 py-2 text-right">Nominal</th>
                <th class="px-3 py-2 text-right">Grand Total</th>
                <th class="px-3 py-2 text-right">Outstanding</th>
                <th class="px-3 py-2">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!isLoading && rows.length === 0">
                <td colspan="10" class="px-3 py-6 text-center text-sm text-gray-500">Tidak ada data. Gunakan filter untuk menampilkan daftar.</td>
              </tr>
              <tr v-for="row in rows" :key="row.id" class="border-t">
                <td class="px-3 py-2">
                  <input type="checkbox" :value="row.id" v-model="selected" />
                </td>
                <td class="px-3 py-2">{{ row.no_po }}</td>
                <td class="px-3 py-2">{{ row.department }}</td>
                <td class="px-3 py-2">{{ formatDate(row.tanggal) }}</td>
                <td class="px-3 py-2">{{ row.perihal }}</td>
                <td class="px-3 py-2">{{ row.supplier }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(row.nominal) }}</td>
                <td class="px-3 py-2 text-right">{{ formatNumber(row.grand_total) }}</td>
                <td class="px-3 py-2 text-right font-semibold">{{ formatNumber(row.outstanding) }}</td>
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2 text-sm">
                    <button class="px-2 py-1 border rounded" @click="viewDetail(row)">Detail</button>
                    <button class="px-2 py-1 border rounded" @click="downloadExcel(row)">Unduh</button>
                    <button class="px-2 py-1 border rounded" @click="viewLog(row)">Log</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center justify-between p-3 border-t text-sm">
          <div>Menampilkan {{ rows.length }} dari {{ pagination.total }} data</div>
          <div class="flex items-center gap-2">
            <button class="px-2 py-1 border rounded" :disabled="!pagination.prev_page_url" @click="goPage(pagination.current_page - 1)">Prev</button>
            <div>Hal {{ pagination.current_page }} / {{ pagination.last_page }}</div>
            <button class="px-2 py-1 border rounded" :disabled="!pagination.next_page_url" @click="goPage(pagination.current_page + 1)">Next</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { FileSpreadsheet } from 'lucide-vue-next';

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'PO Outstanding' },
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  filters: Record<string, any>;
  departments: any[];
}>();

const departments = ref(props.departments || []);
const filters = ref<Record<string, any>>({
  tanggal_start: '',
  tanggal_end: '',
  no_po: '',
  department_id: '',
  supplier_name: '',
  per_page: 10,
});

const isLoading = ref(false);
const rows = ref<any[]>([]);
const pagination = ref<any>({ total: 0, current_page: 1, last_page: 1 });
const selected = ref<number[]>([]);

const allSelected = computed(() => rows.value.length > 0 && selected.value.length === rows.value.length);

function toggleSelectAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  selected.value = checked ? rows.value.map(r => r.id) : [];
}

function formatDate(d: string | null) {
  if (!d) return '';
  const dt = new Date(d);
  if (isNaN(dt.getTime())) return d;
  return dt.toLocaleDateString('id-ID');
}

function formatNumber(n: number | null | undefined) {
  const v = Number(n || 0);
  return v.toLocaleString('id-ID');
}

async function loadData(page?: number) {
  isLoading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(filters.value).forEach(([k, v]) => {
      if (v !== '' && v !== null && v !== undefined) params.set(k, String(v));
    });
    if (page) params.set('page', String(page));

    const { data } = await axios.get(`/po-outstanding/data?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    rows.value = data.data || [];
    pagination.value = data;

    // Clear selection when data changes
    selected.value = [];
  } finally {
    isLoading.value = false;
  }
}

function applyFilters() {
  loadData(1);
}

function resetFilters() {
  filters.value = { tanggal_start: '', tanggal_end: '', no_po: '', department_id: '', supplier_name: '', per_page: 10 };
  rows.value = [];
  pagination.value = { total: 0, current_page: 1, last_page: 1 };
  selected.value = [];
}

function goPage(p: number) {
  if (p < 1 || p > (pagination.value?.last_page || 1)) return;
  loadData(p);
}

function viewDetail(row: any) {
  router.visit(`/purchase-orders/${row.id}`);
}

function downloadExcel(row: any) {
  window.open(`/purchase-orders/${row.id}/download`, '_blank');
}

function viewLog(row: any) {
  router.visit(`/purchase-orders/${row.id}/log`);
}

async function exportExcel() {
  const form = new FormData();
  if (selected.value.length > 0) selected.value.forEach((id) => form.append('ids[]', String(id)));
  Object.entries(filters.value).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined) form.append(k, String(v));
  });
  const res = await fetch('/po-outstanding/export-excel', { method: 'POST', body: form });
  const blob = await res.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'po_outstanding.xlsx';
  a.click();
  window.URL.revokeObjectURL(url);
}

async function exportPdf() {
  const form = new FormData();
  if (selected.value.length > 0) selected.value.forEach((id) => form.append('ids[]', String(id)));
  Object.entries(filters.value).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined) form.append(k, String(v));
  });
  const res = await fetch('/po-outstanding/export-pdf', { method: 'POST', body: form });
  const blob = await res.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'po_outstanding.pdf';
  a.click();
  window.URL.revokeObjectURL(url);
}

onMounted(() => {
  // Default grid empty until filter applied (do not auto-load)
});
</script>
