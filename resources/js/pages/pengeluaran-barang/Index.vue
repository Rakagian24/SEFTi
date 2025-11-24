<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import PengeluaranBarangFilter from '@/components/pengeluaran-barang/PengeluaranBarangFilter.vue';
import PengeluaranBarangTable from '@/components/pengeluaran-barang/PengeluaranBarangTable.vue';
import PengeluaranBarangDetailModal from '@/components/pengeluaran-barang/PengeluaranBarangDetailModal.vue';
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
const selectedIds = ref<number[]>([]);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);
const isBulkDelete = ref(false);
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

const confirmMessage = computed(() =>
  isBulkDelete.value
    ? 'Apakah Anda yakin ingin menghapus semua pengeluaran barang yang dipilih? Tindakan ini tidak dapat dibatalkan.'
    : 'Apakah Anda yakin ingin menghapus pengeluaran barang ini? Tindakan ini tidak dapat dibatalkan.'
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

async function handleDetail(row: any) {
  try {
    const response = await fetch(`/pengeluaran-barang/${row.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('Failed to fetch detail');
    }

    const data = await response.json();
    detailData.value = data;
    showDetailModal.value = true;
  } catch {
    addError('Terjadi kesalahan saat mengambil detail pengeluaran barang');
  }
}

function handleDelete(row: any) {
  confirmRow.value = row;
  showConfirmDialog.value = true;
  isBulkDelete.value = false;
}

function confirmDelete() {
  if (isBulkDelete.value) {
    if (!selectedIds.value || selectedIds.value.length === 0) {
      showConfirmDialog.value = false;
      isBulkDelete.value = false;
      return;
    }

    router.delete('/pengeluaran-barang/bulk-delete', {
      data: { ids: selectedIds.value },
      onSuccess: () => {
        addSuccess('Pengeluaran barang terpilih berhasil dihapus');
        showConfirmDialog.value = false;
        isBulkDelete.value = false;
        selectedIds.value = [];
      },
      onError: () => {
        addError('Terjadi kesalahan saat menghapus pengeluaran barang terpilih');
        showConfirmDialog.value = false;
        isBulkDelete.value = false;
      },
    });
  } else {
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
      },
    });
  }
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
  isBulkDelete.value = false;
}

function handleBulkDeleteClick() {
  if (!selectedIds.value || selectedIds.value.length === 0) {
    addError('Tidak ada data yang dipilih untuk dihapus');
    return;
  }

  isBulkDelete.value = true;
  showConfirmDialog.value = true;
}

function handleExport() {
  const form = new FormData();

  const filters = props.filters || {};
  Object.entries(filters).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    if (Array.isArray(value)) {
      value.forEach((v, index) => {
        form.append(`${key}[${index}]`, String(v));
      });
    } else {
      form.append(key, String(value));
    }
  });

  const tokenMeta = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
  const csrfToken = tokenMeta?.content;

  fetch('/pengeluaran-barang/export-excel', {
    method: 'POST',
    body: form,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    },
    credentials: 'same-origin',
  })
    .then(async (response) => {
      if (!response.ok) {
        throw new Error('Gagal mengunduh file export');
      }
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    })
    .catch(() => {
      addError('Terjadi kesalahan saat melakukan export data');
    });
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
        <div class="mt-4 md:mt-0 flex flex-wrap gap-3 items-center">
          <!-- Add New (black) -->
          <button
            type="button"
            @click="handleAdd"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Add New
          </button>

          <!-- Export to Excel (green outlined) -->
          <button
            type="button"
            @click="handleExport"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
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

      <!-- Filter Section -->
      <PengeluaranBarangFilter
        :filters="props.filters"
        :departments="props.departments || []"
        :jenis-pengeluaran="props.jenisPengeluaran || []"
        :has-selection="selectedIds.length > 0"
        @reset="() => {}"
        @bulk-delete="handleBulkDeleteClick"
      />

      <!-- Table Section -->
      <PengeluaranBarangTable
        v-model="selectedIds"
        :items="pengeluaranBarangData"
        @detail="handleDetail"
        @delete="handleDelete"
        @paginate="handlePagination"
      />

      <!-- Confirm Dialog -->
      <ConfirmDialog
        v-if="showConfirmDialog"
        :show="showConfirmDialog"
        :message="confirmMessage"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />

      <!-- Detail Modal -->
      <PengeluaranBarangDetailModal
        v-if="showDetailModal && detailData"
        :show="showDetailModal"
        :pengeluaran-barang="detailData"
        @close="closeDetailModal"
      />
    </div>
  </div>
</template>
