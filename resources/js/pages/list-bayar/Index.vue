<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Head title="List Bayar" />

      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">List Bayar</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            Kelola daftar pembayaran (filter wajib tanggal)
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="exportPdf"
            :disabled="!exportEnabled || !tanggal.start || !tanggal.end"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Export to PDF
          </button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal (wajib)</label>
            <div class="flex items-center gap-2">
              <input type="date" v-model="tanggalInputStart" class="w-full border rounded px-2 py-1 text-sm" />
              <span class="text-gray-400">s/d</span>
              <input type="date" v-model="tanggalInputEnd" class="w-full border rounded px-2 py-1 text-sm" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
            <select v-model="supplierId" class="w-full border rounded px-2 py-1 text-sm">
              <option :value="undefined">Semua</option>
              <option v-for="opt in supplierOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
            <select v-model="departmentId" class="w-full border rounded px-2 py-1 text-sm">
              <option :value="undefined">Semua</option>
              <option v-for="opt in departmentOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
            <select v-model.number="entriesPerPage" @change="applyFilters" class="w-full border rounded px-2 py-1 text-sm">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
          <button
            @click="applyFilters"
            class="px-3 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
          >
            Apply
          </button>
          <button
            @click="resetFilters"
            class="px-3 py-2 bg-gray-100 text-gray-900 rounded text-sm hover:bg-gray-200"
          >
            Reset
          </button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal PV</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. PV</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="rows.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada data. Pilih rentang tanggal dan Apply.</td>
              </tr>
              <tr v-for="row in rows" :key="row.id">
                <td class="px-4 py-2 text-sm text-gray-900">{{ row.supplier || '-' }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ row.department || '-' }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ formatDate(row.tanggal) }}</td>
                <td class="px-4 py-2 text-sm text-blue-700">
                  <a :href="`/payment-voucher/${row.id}`" class="hover:underline">{{ row.no_pv || '-' }}</a>
                </td>
                <td class="px-4 py-2 text-sm text-right tabular-nums">{{ formatCurrency(row.nominal) }}</td>
                <td class="px-4 py-2 text-sm text-gray-700">{{ row.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center justify-between px-4 py-3 border-t">
          <div class="text-sm text-gray-600">Showing {{ list.from || 0 }} to {{ list.to || 0 }} of {{ list.total || 0 }} entries</div>
          <div class="flex items-center gap-1">
            <button
              v-for="link in (list.links || [])"
              :key="link.url + (link.label || '')"
              :disabled="!link.url"
              @click="paginate(link.url)"
              class="px-3 py-1 text-sm rounded border"
              :class="{
                'bg-gray-100 text-gray-900': !link.active,
                'bg-[#101010] text-white': link.active,
                'opacity-50 cursor-not-allowed': !link.url,
              }"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";

defineOptions({ layout: AppLayout });

const page = usePage();

const breadcrumbs = computed(() => [
  { label: "Home", href: "/dashboard" },
  { label: "List Bayar" },
]);

const list = computed(() => (page.props as any).list || { data: [], links: [] });
const rows = computed(() => (list.value.data || []) as any[]);

const exportEnabled = computed(() => (page.props as any).exportEnabled === true);

// Filters
const entriesPerPage = ref<number>(((page.props as any).filters?.per_page ?? 10) as number);
const supplierOptions = ref(((page.props as any).supplierOptions || []) as Array<{value: any; label: string}>);
const departmentOptions = ref(((page.props as any).departmentOptions || []) as Array<{value: any; label: string}>);

const tanggal = ref<{ start?: Date | null; end?: Date | null }>({
  start: (page.props as any).filters?.tanggal_start ? new Date((page.props as any).filters.tanggal_start) : undefined,
  end: (page.props as any).filters?.tanggal_end ? new Date((page.props as any).filters.tanggal_end) : undefined,
});
const tanggalInputStart = ref<string | undefined>((page.props as any).filters?.tanggal_start || undefined);
const tanggalInputEnd = ref<string | undefined>((page.props as any).filters?.tanggal_end || undefined);
const supplierId = ref<any>((page.props as any).filters?.supplier_id || undefined);
const departmentId = ref<any>((page.props as any).filters?.department_id || undefined);

watch([tanggalInputStart, tanggalInputEnd], () => {
  tanggal.value.start = tanggalInputStart.value ? new Date(tanggalInputStart.value) : undefined;
  tanggal.value.end = tanggalInputEnd.value ? new Date(tanggalInputEnd.value) : undefined;
});

function resetFilters() {
  tanggalInputStart.value = undefined;
  tanggalInputEnd.value = undefined;
  supplierId.value = undefined;
  departmentId.value = undefined;
  entriesPerPage.value = 10;
  router.get("/list-bayar", { per_page: 10 }, { preserveState: true });
}

function applyFilters() {
  const params: Record<string, any> = { per_page: entriesPerPage.value };
  if (tanggalInputStart.value) params.tanggal_start = tanggalInputStart.value;
  if (tanggalInputEnd.value) params.tanggal_end = tanggalInputEnd.value;
  if (supplierId.value) params.supplier_id = supplierId.value;
  if (departmentId.value) params.department_id = departmentId.value;
  router.get("/list-bayar", params, { preserveState: true, preserveScroll: true });
}

function paginate(url: string | null) {
  if (!url) return;
  router.visit(url, { preserveState: true, preserveScroll: true });
}

function exportPdf() {
  if (!tanggalInputStart.value || !tanggalInputEnd.value) return;
  const params = new URLSearchParams({
    tanggal_start: tanggalInputStart.value,
    tanggal_end: tanggalInputEnd.value,
  });
  if (supplierId.value) params.append("supplier_id", String(supplierId.value));
  if (departmentId.value) params.append("department_id", String(departmentId.value));
  window.location.href = `/list-bayar/export-pdf?${params.toString()}`;
}

function formatDate(d?: string | null) {
  if (!d) return '-';
  try { return new Date(d).toLocaleDateString(); } catch { return d; }
}
function formatCurrency(v: any) {
  const num = Number(v || 0);
  return num.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
}
</script>
