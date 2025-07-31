<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import BankAccountTable from "../../components/bank-accounts/BankAccountTable.vue";
import BankAccountFilter from "../../components/bank-accounts/BankAccountFilter.vue";
import BankAccountForm from "../../components/bank-accounts/BankAccountForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import PageHeader from "@/components/PageHeader.vue";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Account" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

interface Bank {
  id: number;
  nama_bank: string;
  singkatan: string;
  status: string;
}

const props = defineProps({
  bankAccounts: Object,
  filters: Object,
  banks: Array as () => Bank[]
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const bankId = ref(props.filters?.bank_id || '');

// Watch for changes and apply filters automatically
watch([entriesPerPage, status, bankId], () => {
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
  if (bankId.value) params.bank_id = bankId.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/bank-accounts', params, {
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
  bankId.value = '';
  entriesPerPage.value = 10;

  router.get('/bank-accounts', { per_page: 10 }, {
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

  router.get('/bank-accounts', params, {
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
  confirmRow.value = row;
  showConfirmDialog.value = true;
}

function confirmDelete() {
  if (!confirmRow.value) return;
  router.delete(`/bank-accounts/${confirmRow.value.id}`, {
    onSuccess: () => {
      clearAll();
      addSuccess('Data bank account berhasil dihapus');
      window.dispatchEvent(new CustomEvent('table-changed'));
      showConfirmDialog.value = false;
      confirmRow.value = null;
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat menghapus data');
      showConfirmDialog.value = false;
      confirmRow.value = null;
    }
  });
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function handleDetail(row: any) {
  router.visit(`/bank-accounts/${row.id}`);
}

function handleToggleStatus(row: any) {
  router.patch(`/bank-accounts/${row.id}/toggle-status`, {}, {
    onSuccess: () => {
      clearAll();
      addSuccess('Status bank account berhasil diperbarui');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat memperbarui status');
    }
  });
}

function handleLog(row: any) {
  router.visit(`/bank-accounts/${row.id}/logs`);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <PageHeader
        title="Bank Account"
        description="Manage Bank Account data"
        @add-click="openAdd"
      />

      <!-- Filter Section -->
      <BankAccountFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:status="status"
        v-model:entries-per-page="entriesPerPage"
        v-model:bank-id="bankId"
        :banks="banks"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <BankAccountTable
        :bank-accounts="props.bankAccounts"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @toggle-status="handleToggleStatus"
        @paginate="handlePagination"
        @log="handleLog"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <BankAccountForm v-if="showForm" :edit-data="editData" :banks="banks" @close="closeForm" />

      <!-- Custom Confirm Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="confirmRow ? `Apakah Anda yakin ingin menghapus data bank account atas nama ${confirmRow.nama_pemilik}?` : ''"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
