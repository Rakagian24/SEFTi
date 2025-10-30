<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import JenisBarangTable from "@/components/jenis-barangs/JenisBarangTable.vue";
import JenisBarangFilter from "@/components/jenis-barangs/JenisBarangFilter.vue";
import JenisBarangForm from "@/components/jenis-barangs/JenisBarangForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import PageHeader from "@/components/PageHeader.vue";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Jenis Barang" },
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

const props = defineProps({
  items: Object,
  filters: Object,
});

const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || "");
const status = ref(props.filters?.status || "");

watch([entriesPerPage, status], () => {
  applyFilters();
});

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

function applyFilters() {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (status.value) params.status = status.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get("/jenis-barangs", params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => window.dispatchEvent(new CustomEvent("table-changed")),
  });
}

function resetFilters() {
  searchQuery.value = "";
  status.value = "";
  entriesPerPage.value = 10;
  router.get("/jenis-barangs", { per_page: 10 }, { preserveState: true });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split("?")[1]);
  const page = urlParams.get("page");
  const params: Record<string, any> = { page };
  if (searchQuery.value) params.search = searchQuery.value;
  if (status.value) params.status = status.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  router.get("/jenis-barangs", params, { preserveState: true, preserveScroll: true });
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
  confirmRow.value = row;
  showConfirmDialog.value = true;
}

function confirmDelete() {
  if (!confirmRow.value) return;
  router.delete(`/jenis-barangs/${confirmRow.value.id}`, {
    onSuccess: () => {
      addSuccess("Data Jenis Barang berhasil dihapus");
      window.dispatchEvent(new CustomEvent("table-changed"));
      showConfirmDialog.value = false;
      confirmRow.value = null;
    },
    onError: () => {
      addError("Terjadi kesalahan saat menghapus data");
      showConfirmDialog.value = false;
      confirmRow.value = null;
    },
  });
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function handleToggleStatus(row: any) {
  router.patch(`/jenis-barangs/${row.id}/toggle-status`, {}, {
    onSuccess: (page: any) => {
      clearAll();
      const flash = page?.props?.flash as any;
      const successMsg = flash && typeof flash.success === 'string' ? flash.success : null;
      addSuccess(successMsg || 'Status Jenis Barang berhasil diperbarui');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat memperbarui status');
    },
  });
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Jenis Barang" description="Manage Jenis Barang" @add-click="openAdd" />

      <JenisBarangFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:status="status"
        v-model:entries-per-page="entriesPerPage"
        @reset="resetFilters"
      />

      <JenisBarangTable
        :items="props.items"
        @edit="openEdit"
        @delete="handleDelete"
        @toggle-status="handleToggleStatus"
        @paginate="handlePagination"
        @add="openAdd"
      />

      <JenisBarangForm v-if="showForm" :edit-data="editData" @close="closeForm" />

      <ConfirmDialog
        :show="showConfirmDialog"
        :message="confirmRow ? `Apakah Anda yakin ingin menghapus data ${confirmRow.nama_jenis_barang}?` : ''"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
