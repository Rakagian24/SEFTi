<script setup lang="ts">
import { ref, watch, PropType } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import ArPartnerTable from "../../components/ar-partners/ArPartnerTable.vue";
import ArPartnerFilter from "../../components/ar-partners/ArPartnerFilter.vue";
import ArPartnerForm from "../../components/ar-partners/ArPartnerForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import PageHeader from "@/components/PageHeader.vue";
const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Customer" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

const props = defineProps({
  ArPartners: Object,
  filters: Object,
  arPartners: Object,
  departments: {
    type: Array as PropType<Array<{ id: string | number, name: string }>>,
    default: () => [],
  },
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const jenis_ap = ref(props.filters?.jenis_ap || '');

// Tambahkan state departmentId
const departmentId = ref(props.filters?.department || '');


// Watch for changes and apply filters automatically
watch([entriesPerPage, jenis_ap, departmentId], () => {
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
  if (jenis_ap.value) params.jenis_ap = jenis_ap.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (departmentId.value) params.department = departmentId.value;

  router.get('/ar-partners', params, {
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
  jenis_ap.value = '';
  entriesPerPage.value = 10;
  departmentId.value = '';

  router.get('/ar-partners', { per_page: 10 }, {
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
  if (jenis_ap.value) params.jenis_ap = jenis_ap.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/ar-partners', params, {
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
  router.delete(`/ar-partners/${row.id}`, {
    onSuccess: () => {
      addSuccess('Data Customer berhasil dihapus');
      // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      addError('Terjadi kesalahan saat menghapus data');
    }
  });
}

function handleDetail(row: any) {
  router.visit(`/ar-partners/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/ar-partners/${row.id}/logs`);
}

function handleMigrate() {
  if (confirm('Apakah Anda yakin ingin menjalankan migrasi data pelanggan dari PostgreSQL?')) {
    router.post('/ar-partners/migrate', {}, {
      onSuccess: () => {
        // Refresh halaman setelah migrasi
        router.reload();
      },
      onError: (errors: any) => {
        addError('Gagal menjalankan migrasi: ' + (errors.message || 'Terjadi kesalahan'));
      }
    });
  }
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <PageHeader
        title="Customer"
        description="Manage Customer data"
        :show-migrate-button="true"
        @add-click="openAdd"
        @migrate-click="handleMigrate"
      />

      <!-- Filter Section -->
      <ArPartnerFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:jenis-ar="jenis_ap"
        v-model:entries-per-page="entriesPerPage"
        :departments="props.departments"
        v-model:department="departmentId"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <ArPartnerTable
        :ar-partners="props.arPartners"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @paginate="handlePagination"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <ArPartnerForm v-if="showForm" :edit-data="editData" :departments="props.departments" @close="closeForm" />
    </div>
  </div>
</template>
