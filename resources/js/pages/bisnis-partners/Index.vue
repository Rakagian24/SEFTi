<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import BisnisPartnerTable from "../../components/bisnis-partners/BisnisPartnerTable.vue";
import BisnisPartnerFilter from "../../components/bisnis-partners/BisnisPartnerFilter.vue";
import BisnisPartnerForm from "../../components/bisnis-partners/BisnisPartnerForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { Handshake } from "lucide-vue-next";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
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
  banks: Array as () => Bank[]
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const jenis_bp = ref(props.filters?.jenis_bp || '');
const terms_of_payment = ref(props.filters?.terms_of_payment || '');

// console.log("bisnisPartners:", props.bisnisPartners);

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
  terms_of_payment.value = '';
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
  if (terms_of_payment.value) params.terms_of_payment = terms_of_payment.value;
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
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bisnis Partner</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Handshake class="w-4 h-4 mr-1" />
            Manage Bisnis Partner data
          </div>
        </div>
        <div class="flex items-center gap-3">
          <!-- Add New Button -->
          <button
            @click="openAdd"
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
      <BisnisPartnerForm v-if="showForm" :edit-data="editData" :banks="banks" @close="closeForm" />
      <ConfirmDialog
        :show="showConfirm"
        :message="rowToDelete && rowToDelete.nama_bp ? `Apakah Anda yakin ingin menghapus data ${rowToDelete.nama_bp}?` : 'Apakah Anda yakin ingin menghapus data ini?'"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
