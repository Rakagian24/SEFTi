<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import BisnisPartnerTable from "../../components/bisnis-partners/BisnisPartnerTable.vue";
import BisnisPartnerFilter from "../../components/bisnis-partners/BisnisPartnerFilter.vue";
import BisnisPartnerForm from "../../components/bisnis-partners/BisnisPartnerForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import PageHeader from "@/components/PageHeader.vue";
const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bisnis Partner" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirm = ref(false);
const rowToDelete = ref<any>(null);

interface Bank {
  id: number;
  nama_bank: string;
  singkatan: string;
  status: string;
}

const props = defineProps({
  BisnisPartners: Object,
  filters: Object,
  bisnisPartners: Object,
  banks: Array as () => Bank[],
  departments: Array as () => Array<{ id: number; name: string }>
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const jenis_bp = ref(props.filters?.jenis_bp || '');


// Watch for changes and apply filters automatically
watch([entriesPerPage, jenis_bp], () => {
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
  if (jenis_bp.value) params.jenis_bp = jenis_bp.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/bisnis-partners', params, {
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
  jenis_bp.value = '';
  entriesPerPage.value = 10;

  router.get('/bisnis-partners', { per_page: 10 }, {
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
  if (jenis_bp.value) params.jenis_bp = jenis_bp.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/bisnis-partners', params, {
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
  showConfirm.value = true;
}

function confirmDelete() {
  if (!rowToDelete.value) return;
  router.delete(`/bisnis-partners/${rowToDelete.value.id}`, {
    onSuccess: () => {
      addSuccess('Data bisnis partner berhasil dihapus');
      window.dispatchEvent(new CustomEvent('table-changed'));
      showConfirm.value = false;
      rowToDelete.value = null;
    },
    onError: (errors) => {
      let msg = 'Terjadi kesalahan saat menghapus data';
      if (errors && errors.message) msg = errors.message;
      addError(msg);
      showConfirm.value = false;
      rowToDelete.value = null;
    }
  });
}

function cancelDelete() {
  showConfirm.value = false;
  rowToDelete.value = null;
}

function handleDetail(row: any) {
  router.visit(`/bisnis-partners/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/bisnis-partners/${row.id}/logs`);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <PageHeader
        title="Bisnis Partner"
        description="Manage Bisnis Partner data"
        @add-click="openAdd"
      />
      <!-- Filter Section -->
      <BisnisPartnerFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:jenis-bp="jenis_bp"
        v-model:entries-per-page="entriesPerPage"
        @reset="resetFilters"
      />
      <!-- Table Section -->
      <BisnisPartnerTable
        :bisnis-partners="props.bisnisPartners"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @paginate="handlePagination"
        @add="openAdd"
      />
      <!-- Form Modal -->
      <BisnisPartnerForm v-if="showForm" :edit-data="editData" :banks="banks" :departments="departments" @close="closeForm" />
      <ConfirmDialog
        :show="showConfirm"
        :message="rowToDelete && rowToDelete.nama_bp ? `Apakah Anda yakin ingin menghapus data ${rowToDelete.nama_bp}?` : 'Apakah Anda yakin ingin menghapus data ini?'"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
