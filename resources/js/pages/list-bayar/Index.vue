<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ListBayarFilter from '@/components/list-bayar/ListBayarFilter.vue';
import ListBayarTable from '@/components/list-bayar/ListBayarTable.vue';
import ListBayarDocumentsTable from '@/components/list-bayar/ListBayarDocumentsTable.vue';
import ListBayarDocumentsFilter from '@/components/list-bayar/ListBayarDocumentsFilter.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { getIconForPage } from '@/lib/iconMapping';
import { useAlertDialog } from '@/composables/useAlertDialog';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'List Bayar' }
];

const { showWarning } = useAlertDialog();

const props = defineProps({
  list: Object,
  documents: Object,
  filters: Object,
  supplierOptions: Array,
  departmentOptions: Array,
  exportEnabled: Boolean,
});

const list = computed(() => props.list);
const documents = computed(() => props.documents);
const filters = computed(() => props.filters as any || {});
const supplierOptions = computed(() => Array.isArray(props.supplierOptions) ? props.supplierOptions : []);
const departmentOptions = computed(() => Array.isArray(props.departmentOptions) ? props.departmentOptions : []);
const exportEnabled = computed(() => props.exportEnabled === true);

const entriesPerPage = ref(props.filters?.per_page || 10);
const documentsEntriesPerPage = ref(10);
const documentsSearch = ref('');
const selectedIds = ref<number[]>([]);
const activeTab = ref<'list' | 'documents'>('list');

// State untuk dialog tanggal export
const isExportDialogOpen = ref(false);
const exportLabelDate = ref<Date | null>(new Date());

function openExportDialog() {
  if (!filters.value.tanggal_start || !filters.value.tanggal_end) {
    showWarning('Silakan pilih rentang tanggal terlebih dahulu untuk export PDF!', 'Peringatan Export');
    return;
  }

  // Default tanggal untuk nama dokumen: hari ini
  exportLabelDate.value = new Date();

  isExportDialogOpen.value = true;
}

function handleFilterChange(newFilters: any) {
  if (activeTab.value !== 'list') return;
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
      selectedIds.value = [];
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
  });
}

function handleDocumentsFiltersChange() {
  if (activeTab.value !== 'documents') return;

  const params: any = {
    ...filters.value,
    per_page: filters.value.per_page || entriesPerPage.value,
    documents_per_page: documentsEntriesPerPage.value,
  };

  if (documentsSearch.value) {
    params.documents_search = documentsSearch.value;
  }

  router.get('/list-bayar', params, {
    preserveState: true,
    preserveScroll: true,
  });
}

watch(documentsEntriesPerPage, () => {
  handleDocumentsFiltersChange();
});

watch(documentsSearch, () => {
  handleDocumentsFiltersChange();
});

function handlePaginate(url: any) {
  if (activeTab.value !== 'list') return;
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
      // Jangan reset selectedIds saat pindah halaman agar checklist tetap tersimpan
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handleResetFilters() {
  if (activeTab.value !== 'list') return;
  entriesPerPage.value = 10;

  const resetParams = {
    per_page: 10,
    page: 1,
  };

  router.get('/list-bayar', resetParams, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = [];
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
  });
}

function exportPdf() {
  if (!exportLabelDate.value) {
    // Tidak ada tanggal yang dipilih
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

  if (selectedIds.value.length > 0) {
    selectedIds.value.forEach((id) => {
      params.append('selected_ids[]', String(id));
    });
  }

  // Simpan label export dalam format yang lebih user-friendly (dd/MM/yyyy)
  const d = exportLabelDate.value;
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const label = `${day}/${month}/${year}`;
  params.append('export_label', label);

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
            @click="openExportDialog"
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

      <!-- Tabs -->
      <div class="mb-4 border-b border-gray-200">
        <nav class="flex -mb-px space-x-4" aria-label="Tabs">
          <button
            type="button"
            @click="activeTab = 'list'"
            :class="[
              'whitespace-nowrap py-2 px-4 border-b-2 text-sm font-medium',
              activeTab === 'list'
                ? 'border-black text-black'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            List Bayar
          </button>
          <button
            type="button"
            @click="activeTab = 'documents'"
            :class="[
              'whitespace-nowrap py-2 px-4 border-b-2 text-sm font-medium',
              activeTab === 'documents'
                ? 'border-black text-black'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
          >
            Dokumen List Bayar
          </button>
        </nav>
      </div>

      <div v-if="activeTab === 'list'">
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
          v-model:selected-ids="selectedIds"
          @paginate="handlePaginate"
        />
      </div>

      <div v-else>
        <ListBayarDocumentsFilter
          v-model:entries-per-page="documentsEntriesPerPage"
          v-model:search="documentsSearch"
        />
        <ListBayarDocumentsTable :documents="documents" />
      </div>

      <!-- Dialog untuk memilih tanggal nama dokumen -->
      <Dialog v-model:open="isExportDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Export List Bayar ke PDF</DialogTitle>
            <DialogDescription>
              Pilih tanggal yang akan dipakai untuk nama dokumen.
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-2 py-4">
            <label class="block text-sm font-medium text-gray-700">
              Tanggal Dokumen
            </label>
            <Datepicker
              v-model="exportLabelDate"
              :enable-time-picker="false"
              :auto-apply="true"
              :clearable="false"
              locale="id"
              :format="'dd MMM yyyy'"
              :input-class="'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5856D6] focus:ring-[#5856D6] text-sm'"
            />
          </div>

          <DialogFooter class="flex justify-end gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50"
              @click="isExportDialogOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm rounded-md bg-[#101010] text-white hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!exportLabelDate"
              @click="() => { exportPdf(); isExportDialogOpen = false; }"
            >
              Export
            </button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </div>
</template>
