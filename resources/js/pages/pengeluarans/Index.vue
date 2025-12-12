<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import PengeluaranTable from "../../components/pengeluarans/PengeluaranTable.vue";
import PengeluaranFilter from "../../components/pengeluarans/PengeluaranFilter.vue";
import PengeluaranForm from "../../components/pengeluarans/PengeluaranForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import PageHeader from "@/components/PageHeader.vue";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Pengeluaran" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

const props = defineProps({
  pengeluarans: Object,
  filters: Object,
  perihalOptions: { type: Array, default: () => [] },
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');

// Watch for changes and apply filters automatically
watch([entriesPerPage], () => {
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
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/pengeluarans', params, {
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
  entriesPerPage.value = 10;

  router.get('/pengeluarans', { per_page: 10 }, {
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
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/pengeluarans', params, {
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
  router.delete(`/pengeluarans/${row.id}`, {
    onSuccess: () => {
      addSuccess('Data pengeluaran berhasil dihapus');
      // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      addError('Terjadi kesalahan saat menghapus data');
    }
  });
}

function handleDetail(row: any) {
  router.visit(`/pengeluarans/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/pengeluarans/${row.id}/logs`);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <PageHeader
        title="Pengeluaran"
        description="Manage Pengeluaran data"
        @add-click="openAdd"
      />

      <!-- Filter Section -->
      <PengeluaranFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:entries-per-page="entriesPerPage"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <PengeluaranTable
        :pengeluarans="props.pengeluarans"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @paginate="handlePagination"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <PengeluaranForm
        v-if="showForm"
        :edit-data="editData"
        :perihal-options="props.perihalOptions"
        @close="closeForm"
      />
    </div>
  </div>
</template>
