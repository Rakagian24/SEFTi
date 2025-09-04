<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold text-gray-900">
          Tolak Dokumen?
        </DialogTitle>
      </DialogHeader>

      <div class="py-4">
        <p class="text-sm text-gray-600 mb-4">
          Apakah Anda yakin ingin menolak dokumen ini? Tindakan ini tidak dapat di batalkan.
        </p>

        <div class="space-y-2">
          <Label for="rejection-reason" class="text-sm font-medium text-gray-700">
            Alasan Penolakan <span class="text-red-500">*</span>
          </Label>
          <textarea
            id="rejection-reason"
            v-model="reason"
            placeholder="Masukkan alasan penolakan..."
            class="w-full min-h-[80px] px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': hasError }"
          ></textarea>
          <p v-if="hasError" class="text-xs text-red-600">
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <DialogFooter class="flex justify-end gap-3">
        <Button
          variant="outline"
          @click="handleCancel"
          class="px-4 py-2"
        >
          Batal
        </Button>
        <Button
          @click="handleConfirm"
          class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white"
        >
          Tolak
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

interface Props {
  isOpen: boolean;
  requireReason?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  requireReason: true
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'cancel': [];
  'confirm': [reason: string];
}>();

const reason = ref("");
const hasError = ref(false);
const errorMessage = ref("");

// Reset form when dialog opens/closes
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    reason.value = "";
    hasError.value = false;
    errorMessage.value = "";
  }
});

const handleCancel = () => {
  reason.value = "";
  hasError.value = false;
  errorMessage.value = "";
  emit('cancel');
};

const handleConfirm = () => {
  // Validate if reason is required
  if (props.requireReason && !reason.value.trim()) {
    hasError.value = true;
    errorMessage.value = "Alasan penolakan wajib diisi";
    return;
  }

  hasError.value = false;
  errorMessage.value = "";
  emit('confirm', reason.value.trim());
};
</script>
