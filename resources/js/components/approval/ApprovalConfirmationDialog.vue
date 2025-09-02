<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold text-gray-900">
          Setujui Dokumen?
        </DialogTitle>
      </DialogHeader>

      <div class="py-4">
        <p class="text-sm text-gray-600">
          Apakah Anda yakin ingin menyetujui dokumen ini? Tindakan ini tidak dapat di batalkan.
        </p>
      </div>

      <DialogFooter class="flex justify-end gap-3">
        <Button
          variant="outline"
          @click="$emit('cancel')"
          class="px-4 py-2"
        >
          Batal
        </Button>
        <Button
          @click="$emit('confirm')"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white"
        >
          Lanjutkan
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface Props {
  isOpen: boolean;
}

defineProps<Props>();

defineEmits<{
  'update:open': [value: boolean];
  'cancel': [];
  'confirm': [];
}>();
</script>
