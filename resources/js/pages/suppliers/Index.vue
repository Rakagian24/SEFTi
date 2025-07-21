<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import SupplierTable from "../../components/suppliers/SupplierTable.vue";
import SupplierFilter from "../../components/suppliers/SupplierFilter.vue";
import SupplierForm from "../../components/suppliers/SupplierForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { UsersRound } from "lucide-vue-next";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Supplier" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

interface Bank {
  id: number;
  nama_bank: string;
  singkatan: string;
  status: string;
}

interface DepartmentOption { id: number|string; name: string; }

const props = defineProps({
  Suppliers: Object,
  filters: Object,
  suppliers: Object,
  banks: Array as () => Bank[],
  departmentOptions: Array as () => DepartmentOption[],
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const termsOfPayment = ref(props.filters?.terms_of_payment || '');
const supplier = ref(props.filters?.supplier || '');
const bank = ref(props.filters?.bank || '');

// Tambahkan state departmentId
const departmentId = ref(props.filters?.department || '');

// Watch for changes and apply filters automatically
watch([entriesPerPage, termsOfPayment, supplier, bank, departmentId], () => {
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
  if (termsOfPayment.value) params.terms_of_payment = termsOfPayment.value;
  if (supplier.value) params.supplier = supplier.value;
  if (bank.value) params.bank = bank.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (departmentId.value) params.department = departmentId.value;

  router.get('/suppliers', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function resetFilters() {
  searchQuery.value = '';
  termsOfPayment.value = '';
  supplier.value = '';
  bank.value = '';
  entriesPerPage.value = 10;
  departmentId.value = '';

  router.get('/suppliers', { per_page: 10 }, {
    preserveState: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handlePagination(url: string) {
  if (!url) return;

  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');

  const params: Record<string, any> = { page };

  if (searchQuery.value) params.search = searchQuery.value;
  if (termsOfPayment.value) params.terms_of_payment = termsOfPayment.value;
  if (supplier.value) params.supplier = supplier.value;
  if (bank.value) params.bank = bank.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (departmentId.value) params.department = departmentId.value;

  router.get('/suppliers', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
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
  // HAPUS confirm browser, langsung hapus
  router.delete(`/suppliers/${row.id}`, {
    onSuccess: () => {
      addSuccess('Data supplier berhasil dihapus');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      addError('Terjadi kesalahan saat menghapus data');
    }
  });
}

function handleDetail(row: any) {
  router.visit(`/suppliers/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/suppliers/${row.id}/logs`);
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
          <h1 class="text-2xl font-bold text-gray-900">Supplier</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <UsersRound class="w-4 h-4 mr-1" />
            Manage Supplier data
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
      <SupplierFilter
        :filters="filters"
        :suppliers="props.suppliers"
        :banks="banks"
        v-model:search="searchQuery"
        v-model:terms-of-payment="termsOfPayment"
        v-model:supplier="supplier"
        v-model:bank="bank"
        v-model:entries-per-page="entriesPerPage"
        :departments="props.departmentOptions"
        v-model:department="departmentId"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <SupplierTable
        :suppliers="props.suppliers"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @paginate="handlePagination"
      />

      <!-- Form Modal -->
      <SupplierForm v-if="showForm" :edit-data="editData" :banks="banks" :department-options="departmentOptions" @close="closeForm" />
    </div>
  </div>
</template>
