<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BankKeluarFilter from '@/components/bank-keluar/BankKeluarFilter.vue';
import BankKeluarTable from '@/components/bank-keluar/BankKeluarTable.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { formatDate } from '@/lib/dateUtils';

const props = defineProps({
  bankKeluars: Object,
  filters: Object,
  departments: Array,
  suppliers: Array,
  sortBy: String,
  sortDirection: String,
  per_page: Number,
});

const showConfirmDelete = ref(false);
const bankKeluarToDelete = ref(null);

const title = computed(() => {
  return 'Bank Keluar';
});

const subtitle = computed(() => {
  if (props.filters.start && props.filters.end) {
    return `${formatDate(props.filters.start)} - ${formatDate(props.filters.end)}`;
  }
  return 'Semua Data';
});

function handleFilter(filters) {
  router.get(route('bank-keluar.index'), filters, {
    preserveState: true,
    replace: true,
  });
}

function handleSort({ sortBy, sortDirection }) {
  router.get(
    route('bank-keluar.index'),
    { ...props.filters, sortBy, sortDirection },
    {
      preserveState: true,
      replace: true,
    }
  );
}

function handlePaginate(url) {
  router.get(url);
}

function handleEdit(bankKeluar) {
  router.get(route('bank-keluar.edit', bankKeluar.id));
}

function handleDetail(bankKeluar) {
  router.get(route('bank-keluar.show', bankKeluar.id));
}

function handleLog(bankKeluar) {
  router.get(route('bank-keluar.log', bankKeluar.id));
}

function confirmDelete(bankKeluar) {
  bankKeluarToDelete.value = bankKeluar;
  showConfirmDelete.value = true;
}

function handleDelete() {
  if (bankKeluarToDelete.value) {
    router.delete(route('bank-keluar.destroy', bankKeluarToDelete.value.id), {
      onSuccess: () => {
        showConfirmDelete.value = false;
        bankKeluarToDelete.value = null;
      },
    });
  }
}

function handleExportExcel() {
  router.post(route('bank-keluar.export-excel'), props.filters, { preserveState: true });
}

function handleCreate() {
  router.get(route('bank-keluar.create'));
}
</script>

<template>
  <AppLayout>
    <Head :title="title" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ title }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ subtitle }}</p>
          </div>
          <div class="flex space-x-2">
            <button
              @click="handleExportExcel"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              <svg
                class="mr-2 -ml-1 h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              Export Excel
            </button>
            <button
              @click="handleCreate"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg
                class="mr-2 -ml-1 h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
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

        <div class="mt-6">
          <BankKeluarFilter
            :filters="filters"
            :departments="departments"
            :suppliers="suppliers"
            @filter="handleFilter"
          />
        </div>

        <div class="mt-6">
          <BankKeluarTable
            :bankKeluars="bankKeluars"
            :sortBy="sortBy"
            :sortDirection="sortDirection"
            @edit="handleEdit"
            @delete="confirmDelete"
            @detail="handleDetail"
            @log="handleLog"
            @paginate="handlePaginate"
            @sort="handleSort"
          />
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="showConfirmDelete"
      title="Konfirmasi Pembatalan"
      message="Apakah Anda yakin ingin membatalkan Bank Keluar ini?"
      @confirm="handleDelete"
      @cancel="showConfirmDelete = false"
    />
  </AppLayout>
</template>
