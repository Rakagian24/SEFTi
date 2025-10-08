<template>
  <AlertDialog
    :is-open="alertState.isOpen"
    :type="alertState.type"
    :title="alertState.title"
    :message="alertState.message"
    :confirm-text="alertState.confirmText"
    :cancel-text="alertState.cancelText"
    :show-cancel="alertState.showCancel"
    @update:open="handleUpdateOpen"
    @confirm="handleConfirm"
    @cancel="handleCancel"
  />
</template>

<script setup lang="ts">
import AlertDialog from '@/components/ui/AlertDialog.vue';
import { useAlertDialog } from '@/composables/useAlertDialog';

const { alertState, handleConfirm, handleCancel } = useAlertDialog();

function handleUpdateOpen(isOpen: boolean) {
  if (!isOpen) {
    handleCancel();
  }
}
</script>
