<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import BankTable from "../../components/banks/BankTable.vue";
import BankFilter from "../../components/banks/BankFilter.vue";
import BankForm from "../../components/banks/BankForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { Landmark } from "lucide-vue-next";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

const props = defineProps({
  Banks: Object,
  filters: Object,
  banks: Object
});

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

// console.log("banks:", props.banks);

// Watch for changes and apply filters automatically
watch([entriesPerPage, status], () => {
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
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get('/banks', params, {
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
  entriesPerPage.value = 10;

  router.get('/banks', { per_page: 10 }, {
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

  router.get('/banks', params, {
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
  if (confirm(`Apakah Anda yakin ingin menghapus data bank ${row.nama_bank}?`)) {
    router.delete(`/banks/${row.id}`, {
      onSuccess: () => {
        addSuccess('Data bank berhasil dihapus');
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent('table-changed'));
      },
      onError: (errors) => {
        console.error('Error deleting data:', errors);
        addError('Terjadi kesalahan saat menghapus data');
      }
    });
  }
}

function handleDetail(row: any) {
  router.visit(`/banks/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/banks/${row.id}/logs`);
}

function handleToggleStatus(row: any) {
  router.patch(`/banks/${row.id}/toggle-status`, {}, {
    onSuccess: (page) => {
      clearAll();
      const flash = page?.props?.flash as any;
      const successMsg = flash && typeof flash.success === 'string' ? flash.success : null;
      addSuccess(successMsg || 'Status bank berhasil diperbarui');
      // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat memperbarui status');
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
          <h1 class="text-2xl font-bold text-gray-900">Bank</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Landmark class="w-4 h-4 mr-1" />
            Manage Bank data
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
      <BankFilter
        :filters="filters"
        v-model:search="searchQuery"
        v-model:status="status"
        v-model:entries-per-page="entriesPerPage"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <BankTable
        :banks="props.Banks"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @toggle-status="handleToggleStatus"
        @paginate="handlePagination"
      />

      <!-- Form Modal -->
      <BankForm v-if="showForm" :edit-data="editData" @close="closeForm" />
    </div>
  </div>
</template>
