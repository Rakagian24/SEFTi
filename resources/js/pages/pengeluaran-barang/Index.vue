<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import PengeluaranBarangFilter from '@/components/pengeluaran-barang/PengeluaranBarangFilter.vue';
import PengeluaranBarangTable from '@/components/pengeluaran-barang/PengeluaranBarangTable.vue';
import PengeluaranBarangDetail from '@/components/pengeluaran-barang/PengeluaranBarangDetail.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';

defineOptions({ layout: AppLayout });

const props = defineProps<{
  pengeluaranBarang: any;
  filters?: Record<string, any>;
  departments?: { id: number; name: string }[];
  jenisPengeluaran?: { id: string; name: string }[];
}>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Pengeluaran Barang' }
];

// State
const pengeluaranBarangData = ref(props.pengeluaranBarang || { data: [], total: 0, current_page: 1, last_page: 1 });
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);
const showDetailModal = ref(false);
const detailData = ref<any>(null);

// Global message panel integration
const page = usePage();
const { addSuccess, addError } = useMessagePanel();

watch(
  () => page.props,
  (newProps: any) => {
    const flash = newProps?.flash || {};
    if (typeof flash.success === 'string' && flash.success) addSuccess(flash.success);
    if (typeof flash.error === 'string' && flash.error) addError(flash.error);
  },
  { immediate: true }
);

watch(
  () => props.pengeluaranBarang,
  (newData) => {
    if (newData) {
      pengeluaranBarangData.value = newData;
    }
  }
);

// Functions
function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  if (page) {
    router.get(`/pengeluaran-barang?page=${page}`, {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        window.dispatchEvent(new CustomEvent('table-changed'));
      }
    });
  }
}

function handleDetail(row: any) {
  router.get(`/pengeluaran-barang/${row.id}`, {}, {
    onSuccess: (page) => {
      detailData.value = page.props.pengeluaranBarang;
      showDetailModal.value = true;
    },
    onError: () => {
      addError('Terjadi kesalahan saat mengambil detail pengeluaran barang');
    }
  });
}

function handleDelete(row: any) {
  confirmRow.value = row;
  showConfirmDialog.value = true;
}

function confirmDelete() {
  if (!confirmRow.value) return;

  router.delete(`/pengeluaran-barang/${confirmRow.value.id}`, {
    onSuccess: () => {
      addSuccess('Pengeluaran barang berhasil dihapus');
      showConfirmDialog.value = false;
      confirmRow.value = null;
    },
    onError: () => {
      addError('Terjadi kesalahan saat menghapus pengeluaran barang');
      showConfirmDialog.value = false;
      confirmRow.value = null;
    }
  });
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function handleExport() {
  // Implement export functionality if needed
  addError('Export functionality is not implemented yet');
}

function handleAdd() {
  router.visit('/pengeluaran-barang/create');
}

function closeDetailModal() {
  showDetailModal.value = false;
  detailData.value = null;
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Pengeluaran Barang</h1>
          <p class="text-gray-600 mt-1">Kelola data pengeluaran barang</p>
        </div>
      </div>

      <!-- Filter Section -->
      <PengeluaranBarangFilter
        :filters="props.filters"
        :departments="props.departments || []"
        :jenis-pengeluaran="props.jenisPengeluaran || []"
        @reset="() => {}"
        @export="handleExport"
        @add="handleAdd"
      />

      <!-- Table Section -->
      <PengeluaranBarangTable
        :items="pengeluaranBarangData"
        @detail="handleDetail"
        @delete="handleDelete"
        @paginate="handlePagination"
      />

      <!-- Confirm Dialog -->
      <ConfirmDialog
        v-if="showConfirmDialog"
        :show="showConfirmDialog"
        message="Apakah Anda yakin ingin menghapus pengeluaran barang ini? Tindakan ini tidak dapat dibatalkan."
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />

      <!-- Detail Modal -->
      <PengeluaranBarangDetail
        v-if="showDetailModal && detailData"
        :pengeluaran-barang="detailData"
        @close="closeDetailModal"
      />
    </div>
  </div>
</template>
