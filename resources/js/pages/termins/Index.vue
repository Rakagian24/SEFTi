<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import TerminTable from "../../components/termins/TerminTable.vue";
import TerminFilter from "../../components/termins/TerminFilter.vue";
import TerminForm from "../../components/termins/TerminForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";

defineOptions({ layout: AppLayout });

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Termin" }];

const { addSuccess, addError } = useMessagePanel();

const props = defineProps<{ termins: any, filters: Record<string, any>, departmentOptions?: Array<{id:number,name:string}> }>();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirmDialog = ref(false);
const confirmRow = ref<{ id: number } | null>(null);

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
  if (confirmRow.value) {
    router.delete(`/termins/${confirmRow.value.id}`, {
      onSuccess: () => {
        addSuccess('Data termin berhasil dihapus');
        window.dispatchEvent(new CustomEvent('termins-table-changed'));
      },
      onError: () => {
        addError('Gagal menghapus data termin');
      }
    });
  }
  cancelDelete();
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function handleToggleStatus(row: any) {
  router.patch(`/termins/${row.id}/toggle-status`, {}, {
    onSuccess: () => {
      addSuccess('Status termin berhasil diperbarui');
      window.dispatchEvent(new CustomEvent('termins-table-changed'));
    },
    onError: () => {
      addError('Gagal memperbarui status termin');
    }
  });
}

function applyFilters(payload: Record<string, any>) {
  const params: Record<string, any> = {};
  if (payload.search) params.search = payload.search;
  if (payload.status) params.status = payload.status;
  if (payload.per_page) params.per_page = payload.per_page;
  if (payload.department_id !== undefined && payload.department_id !== null) params.department_id = payload.department_id;

  router.get('/termins', params, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
  router.get('/termins', { per_page: 10 }, { preserveState: true });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  router.get('/termins', { ...props.filters, page }, { preserveState: true, preserveScroll: true });
}

// Listen for table changes to refresh data (scoped event to avoid conflicts)
onMounted(() => {
  window.addEventListener('termins-table-changed', () => {
    router.reload({ only: ['termins'] });
  });
});
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Termin</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Manage Termin data
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
      <TerminFilter
        :filters="filters"
        :departments="props.departmentOptions || []"
        @filter="applyFilters"
        @reset="resetFilters"
      />

      <!-- Table Section -->
      <TerminTable
        :termins="props.termins"
        @edit="openEdit"
        @delete="handleDelete"
        @toggle-status="handleToggleStatus"
        @paginate="handlePagination"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <TerminForm v-if="showForm" :edit-data="editData" :department-options="props.departmentOptions || []" @close="closeForm" />

      <!-- Custom Confirm Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="confirmRow ? `Apakah Anda yakin ingin menghapus data termin ini?` : ''"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
