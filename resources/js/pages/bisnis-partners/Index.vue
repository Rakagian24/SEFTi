<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import BisnisPartnerTable from "../../components/bisnis-partners/BisnisPartnerTable.vue";
import BisnisPartnerFilter from "../../components/bisnis-partners/BisnisPartnerFilter.vue";
import BisnisPartnerForm from "../../components/bisnis-partners/BisnisPartnerForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
const breadcrumbs = [
  { label: "Home", href: "/" },
  { label: "Bisnis Partner" }
];

defineOptions({ layout: AppLayout });

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

const props = defineProps({
  arPartners: Object,
  filters: Object,
  bisnisPartners: Object
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const jenis_bp = ref(props.filters?.jenis_bp || '');
const terms_of_payment = ref(props.filters?.terms_of_payment || '');

console.log("bisnisPartners:", props.bisnisPartners);

// Watch for changes and apply filters automatically
watch([entriesPerPage, jenis_bp, terms_of_payment], () => {
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
  if (terms_of_payment.value) params.terms_of_payment = terms_of_payment.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/bisnis-partners', params, {
    preserveState: true,
    preserveScroll: true
  });
}

function resetFilters() {
  searchQuery.value = '';
  jenis_bp.value = '';
  terms_of_payment.value = '';
  entriesPerPage.value = 10;

  router.get('/bisnis-partners', { per_page: 10 }, {
    preserveState: true
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
  if (terms_of_payment.value) params.terms_of_payment = terms_of_payment.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/bisnis-partners', params, {
    preserveState: true,
    preserveScroll: true
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
  if (confirm(`Apakah Anda yakin ingin menghapus data ${row.nama_bp}?`)) {
    router.delete(`/bisnis-partners/${row.id}`, {
      onSuccess: () => {
        alert('Data berhasil dihapus');
      },
      onError: (errors) => {
        console.error('Error deleting data:', errors);
        alert('Terjadi kesalahan saat menghapus data');
      }
    });
  }
}

function handleDetail(row: any) {
  router.visit(`/bisnis-partners/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/bisnis-partners/${row.id}/logs`);
}
</script>

<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="p-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bisnis Partner</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <svg
              class="w-4 h-4 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
            Manage Bisnis Partner data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Add New Button -->
          <button
            @click="openAdd"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
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
        </div>
      </div>

      <!-- Filter Section -->
      <BisnisPartnerFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:jenis-bp="jenis_bp"
        v-model:terms-of-payment="terms_of_payment"
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
      />

      <!-- Form Modal -->
      <BisnisPartnerForm v-if="showForm" :edit-data="editData" @close="closeForm" />
    </div>
  </div>
</template>
