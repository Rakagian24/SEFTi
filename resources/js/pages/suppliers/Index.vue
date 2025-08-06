<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import SupplierTable from "../../components/suppliers/SupplierTable.vue";
import SupplierFilter from "../../components/suppliers/SupplierFilter.vue";
import SupplierForm from "../../components/suppliers/SupplierForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import PageHeader from "@/components/PageHeader.vue";

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
      <PageHeader
        title="Supplier"
        description="Manage Supplier data"
        @add-click="openAdd"
      />

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
        :department-options="props.departmentOptions"
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
        @add="openAdd"
      />

      <!-- Form Modal -->
      <SupplierForm v-if="showForm" :edit-data="editData" :banks="banks" :department-options="departmentOptions" @close="closeForm" />
    </div>
  </div>
</template>
