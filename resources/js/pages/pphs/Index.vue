<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import PphTable from "../../components/pphs/PPhTable.vue";
import PphFilter from "../../components/pphs/PPhFilter.vue";
import PphForm from "../../components/pphs/PPhForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import PageHeader from "@/components/PageHeader.vue";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Pajak Penghasilan (PPh)" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirmDelete = ref(false);
const rowToDelete = ref<any>(null);

const props = defineProps({
  pphs: Object,
  filters: Object,
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

// Watch for changes and apply filters automatically
watch([entriesPerPage, status], () => {
  applyFilters();
}, { immediate: false });

// Watch search query with debouncing
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500); // 500ms debounce
}, { immediate: false });

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (status.value) params.status = status.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/pphs', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function resetFilters() {
  searchQuery.value = '';
  status.value = '';
  entriesPerPage.value = 10;

  router.get('/pphs', { per_page: 10 }, {
    preserveState: true,
    onSuccess: () => {
      // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handlePagination(url: string) {
  if (!url) return;

  // Extract page number from URL
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');

  const params: Record<string, any> = { page };

  if (searchQuery.value) params.search = searchQuery.value;
  if (status.value) params.status = status.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/pphs', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function openAdd() {
  editData.value = undefined;
  showForm.value = true;
}

function openEdit(row: any) {
  editData.value = row;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editData.value = undefined;
}

function handleDelete(row: any) {
  rowToDelete.value = row;
  showConfirmDelete.value = true;
}

function confirmDelete() {
  if (!rowToDelete.value) return;
  router.delete(`/pphs/${rowToDelete.value.id}`, {
    onSuccess: () => {
      clearAll();
      addSuccess('Data PPh berhasil dihapus');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat menghapus data');
    }
  });
  showConfirmDelete.value = false;
  rowToDelete.value = null;
}

function cancelDelete() {
  showConfirmDelete.value = false;
  rowToDelete.value = null;
}

function handleDetail(row: any) {
  router.visit(`/pphs/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/pphs/${row.id}/logs`);
}
function handleToggleStatus(row: any) {
  router.patch(`/pphs/${row.id}/toggle-status`, {}, {
    onSuccess: () => {
      clearAll();
      addSuccess('Status pph berhasil diperbarui');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat memperbarui status');
    }
  });
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <PageHeader
        title="PPh"
        description="Manage Pajak Penghasilan (PPh) data"
        @add-click="openAdd"
      />

      <!-- Filter Section -->
      <PphFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:status="status"
        v-model:entries-per-page="entriesPerPage"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <PphTable
        :pphs="props.pphs"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @paginate="handlePagination"
        @toggleStatus="handleToggleStatus"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <PphForm v-if="showForm" :edit-data="editData" @close="closeForm" />
      <!-- Confirm Delete Dialog -->
      <ConfirmDialog
        :show="showConfirmDelete"
        :message="rowToDelete && rowToDelete.nama_pph ? `Apakah Anda yakin ingin menghapus data PPh ${rowToDelete.nama_pph}?` : 'Apakah Anda yakin ingin menghapus data ini?'"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
