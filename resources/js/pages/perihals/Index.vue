<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import PerihalTable from '@/components/perihals/PerihalTable.vue';
import PerihalForm from '@/components/perihals/PerihalForm.vue';
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from '@/composables/useMessagePanel';
import { getIconForPage } from "@/lib/iconMapping";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Master Perihal" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

const props = defineProps({
  perihals: Object,
});



function handlePagination(url: string) {
  if (!url) return;

  // Extract page number from URL
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');

  const params: Record<string, any> = { page };

  router.get('/perihals', params, {
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
  router.delete(`/perihals/${confirmRow.value.id}`, {
    onSuccess: () => {
      addSuccess('Data perihal berhasil dihapus');
      window.dispatchEvent(new CustomEvent('table-changed'));
      showConfirmDialog.value = false;
      confirmRow.value = null;
    },
    onError: () => {
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
  router.visit(`/perihals/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/perihals/${row.id}/logs`);
}

function handleToggleStatus(row: any) {
  router.patch(`/perihals/${row.id}/toggle-status`, {}, {
    onSuccess: (page) => {
      clearAll();
      const flash = page?.props?.flash as any;
      const successMsg = flash && typeof flash.success === 'string' ? flash.success : null;
      addSuccess(successMsg || 'Status perihal berhasil diperbarui');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat memperbarui status');
    }
  });
}



// Handle flash messages and setup event listeners
onMounted(() => {
  // Handle flash messages from server
  const page = usePage();
  const flash = page.props.flash as any;

  if (flash?.success) {
    addSuccess(flash.success);
  }
  if (flash?.error) {
    addError(flash.error);
  }

  // Setup event listeners
  window.addEventListener('table-changed', () => {
    // Refresh data when table changes
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
          <h1 class="text-2xl font-bold text-gray-900">Perihal</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <component :is="getIconForPage('Perihal')" class="w-4 h-4 mr-1" />
            Manage Perihal data
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



      <!-- Table Section -->
      <PerihalTable
        :perihals="props.perihals"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @toggle-status="handleToggleStatus"
        @paginate="handlePagination"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <PerihalForm v-if="showForm" :edit-data="editData" @close="closeForm" />

      <!-- Custom Confirm Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="confirmRow ? `Apakah Anda yakin ingin menghapus data perihal ini?` : ''"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
