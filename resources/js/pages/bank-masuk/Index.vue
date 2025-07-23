<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import BankMasukFilter from '@/components/bank-masuk/BankMasukFilter.vue';
import BankMasukForm from '@/components/bank-masuk/BankMasukForm.vue';
import BankMasukTable from '@/components/bank-masuk/BankMasukTable.vue';
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { CreditCard } from "lucide-vue-next";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Masuk" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const page = usePage();
const bankMasuks = page.props.bankMasuks;
const filters = page.props.filters || {};
const bankAccounts = Array.isArray(page.props.bankAccounts) ? page.props.bankAccounts : [];

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

function openForm(row: Record<string, any> | undefined = undefined) {
  editData.value = row;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editData.value = undefined;
}

function handleFilterChange(newFilters: any) {
  router.get('/bank-masuk', { ...filters, ...newFilters }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handlePaginate(url: any) {
  router.get(url, {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handleEdit(row: any) {
  openForm(row);
}

function handleDetail(row: any) {
  router.get(`/bank-masuk/${row.id}`);
}

function handleLog(row: any) {
  router.get(`/bank-masuk/${row.id}/log`);
}

function handleDelete(row: any) {
  // Remove browser confirm, direct delete like supplier
  router.delete(`/bank-masuk/${row.id}`, {
    onSuccess: () => {
      addSuccess('Data bank masuk berhasil dihapus');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      addError('Terjadi kesalahan saat menghapus data');
    }
  });
}

function handleRefreshTable() {
  router.get('/bank-masuk', { ...filters }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
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
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bank Masuk</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Manage Bank Masuk data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Add New Button -->
          <button
            @click="openForm()"
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
      <BankMasukFilter
        :filters="filters"
        :bankAccounts="bankAccounts"
        @change="handleFilterChange"
      />

      <!-- Table Section -->
      <BankMasukTable
        :bankMasuks="bankMasuks"
        @edit="handleEdit"
        @detail="handleDetail"
        @log="handleLog"
        @delete="handleDelete"
        @paginate="handlePaginate"
      />

      <!-- Form Modal -->
      <BankMasukForm
        v-if="showForm"
        :editData="editData"
        :bankAccounts="bankAccounts"
        @close="closeForm"
        @refreshTable="handleRefreshTable"
      />
    </div>
  </div>
</template>
