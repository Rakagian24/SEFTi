<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ListBayarFilter from '@/components/list-bayar/ListBayarFilter.vue';
import ListBayarTable from '@/components/list-bayar/ListBayarTable.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
// import { getIconForPage } from '@/lib/iconMapping';
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

const { showWarning } = useAlertDialog();

const props = defineProps({
  document: Object,
  list: Object,
  selectedIds: Array,
  filters: Object,
  supplierOptions: Array,
  departmentOptions: Array,
});

const breadcrumbs = computed(() => [
  { label: 'Home', href: '/dashboard' },
  { label: 'Daftar List Bayar', href: '/list-bayar' },
  { label: props.document?.no_list_bayar || 'Edit Dokumen List Bayar' },
]);

const list = computed(() => props.list);
const filters = computed(() => props.filters as any || {});
const supplierOptions = computed(() => Array.isArray(props.supplierOptions) ? props.supplierOptions : []);
const departmentOptions = computed(() => Array.isArray(props.departmentOptions) ? props.departmentOptions : []);

const entriesPerPage = ref(props.filters?.per_page || 10);
const selectedIds = ref<number[]>(Array.isArray(props.selectedIds) ? (props.selectedIds as number[]) : []);

// State dialog untuk tanggal export dokumen
const isExportDialogOpen = ref(false);
const exportLabelDate = ref<Date | null>(props.document?.tanggal ? new Date(props.document.tanggal as string) : new Date());

function openExportDialog() {
  if (!selectedIds.value.length) {
    showWarning('Silakan pilih minimal satu Payment Voucher sebelum export PDF!', 'Peringatan Export');
    return;
  }

  // Default tanggal untuk nama dokumen: hari ini atau dari document
  if (props.document?.tanggal) {
    try {
      exportLabelDate.value = new Date(props.document.tanggal as string);
    } catch {
      exportLabelDate.value = new Date();
    }
  } else {
    exportLabelDate.value = new Date();
  }

  isExportDialogOpen.value = true;
}

function handleFilterChange(newFilters: any) {
  const filterParams = {
    ...filters.value,
    ...newFilters,
    per_page: newFilters.per_page || entriesPerPage.value,
    page: 1,
    // Pastikan tanggal dan filter lain ikut diteruskan
    tanggal_start: newFilters.tanggal_start || (filters.value as any).tanggal_start,
    tanggal_end: newFilters.tanggal_end || (filters.value as any).tanggal_end,
    supplier_id: newFilters.supplier_id || filters.value.supplier_id,
    department_id: newFilters.department_id || filters.value.department_id,
  };

  router.get(`/list-bayar/documents/${props.document?.id}/edit`, filterParams, {
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
  router.get(`/list-bayar/documents/${props.document?.id}/edit`, {
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

  router.get(`/list-bayar/documents/${props.document?.id}/edit`, resetParams, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
  });
}

function exportPdf() {
  if (!selectedIds.value.length || !exportLabelDate.value) {
    return;
  }

  const params = new URLSearchParams();

  selectedIds.value.forEach((id) => {
    params.append('selected_ids[]', String(id));
  });

  // Simpan label export dalam format yang lebih user-friendly (dd/MM/yyyy)
  const d = exportLabelDate.value;
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const label = `${day}/${month}/${year}`;
  params.append('export_label', label);

  window.location.href = `/list-bayar/documents/${props.document?.id}/export-pdf?${params.toString()}`;
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Head :title="`Edit Dokumen List Bayar - ${document?.no_list_bayar || ''}`" />

      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Dokumen List Bayar</h1>
          <div class="mt-1 text-sm text-gray-600">
            <div>No List Bayar: <span class="font-semibold">{{ document?.no_list_bayar }}</span></div>
            <div>Tanggal Dokumen: <span class="font-semibold">{{ document?.tanggal || '-' }}</span></div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="openExportDialog"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200 border border-transparent hover:border-[#101010]"
          >
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
        v-model:selected-ids="selectedIds"
        @paginate="handlePaginate"
      />

      <!-- Dialog untuk memilih tanggal nama dokumen -->
      <Dialog v-model:open="isExportDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Export Dokumen List Bayar ke PDF</DialogTitle>
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
