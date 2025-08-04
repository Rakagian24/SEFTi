<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import RoleTable from '@/components/roles/RoleTable.vue';
import RoleForm from '@/components/roles/RoleForm.vue';
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from '@/composables/useMessagePanel';
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import PageHeader from "@/components/PageHeader.vue";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Role" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

const props = defineProps({
  roles: Object
});

// console.log("roles:", props.roles);

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
  router.delete(`/roles/${confirmRow.value.id}`, {
    onSuccess: () => {
      addSuccess('Data role berhasil dihapus');
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
  router.visit(`/roles/${row.id}`);
}

function handleLog(row: any) {
  router.visit(`/roles/${row.id}/logs`);
}

function handleToggleStatus(row: any) {
  router.patch(`/roles/${row.id}/toggle-status`, {}, {
    onSuccess: (page) => {
      clearAll();
      const flash = page?.props?.flash as any;
      const successMsg = flash && typeof flash.success === 'string' ? flash.success : null;
      addSuccess(successMsg || 'Status role berhasil diperbarui');
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
    onError: () => {
      clearAll();
      addError('Terjadi kesalahan saat memperbarui status');
    }
  });
}

onMounted(() => {
  window.addEventListener('table-changed', () => {
    // Refresh data when table changes
  });
});
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <PageHeader
        title="Role"
        description="Manage Role data"
        @add-click="openAdd"
      />

      <!-- Table Section -->
      <RoleTable
        :roles="props.roles"
        @edit="openEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @log="handleLog"
        @toggle-status="handleToggleStatus"
        @add="openAdd"
      />

      <!-- Form Modal -->
      <RoleForm v-if="showForm" :edit-data="editData" @close="closeForm" />

      <!-- Custom Confirm Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="confirmRow ? `Apakah Anda yakin ingin menghapus data role ${confirmRow.nama_role || confirmRow.name}?` : ''"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>
