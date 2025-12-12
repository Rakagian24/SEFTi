<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold text-gray-900">
          Tutup Dokumen?
        </DialogTitle>
      </DialogHeader>

      <div class="py-4">
        <p class="text-sm text-gray-600 mb-4">
          Apakah Anda yakin ingin menutup (Closed) dokumen ini? Tindakan ini tidak dapat
          dibatalkan.
        </p>

        <div class="space-y-2">
          <Label for="close-reason" class="text-sm font-medium text-gray-700">
            Alasan Penutupan<span class="text-red-500">*</span>
          </Label>
          <textarea
            id="close-reason"
            v-model="reason"
            placeholder="Masukkan alasan penutupan..."
            class="w-full min-h-[80px] px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
            :class="{
              'border-red-300 focus:border-red-500 focus:ring-red-500': hasError,
            }"
          ></textarea>
          <p v-if="hasError" class="text-xs text-red-600">
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <DialogFooter class="flex justify-end gap-3">
        <Button variant="outline" @click="handleCancel" class="px-4 py-2">
          Batal
        </Button>
        <Button
          @click="handleConfirm"
          class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white"
        >
          Tutup (Closed)
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

interface Props {
  isOpen: boolean;
  requireReason?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  requireReason: true,
});

const emit = defineEmits<{
  "update:open": [value: boolean];
  cancel: [];
  confirm: [reason: string];
}>();

const reason = ref("");
const hasError = ref(false);
const errorMessage = ref("");

// Reset form when dialog opens/closes
watch(
  () => props.isOpen,
  (newValue) => {
    if (newValue) {
      reason.value = "";
      hasError.value = false;
      errorMessage.value = "";
    }
  }
);

const handleCancel = () => {
  reason.value = "";
  hasError.value = false;
  errorMessage.value = "";
  emit("cancel");
};

const handleConfirm = () => {
  // Validate if reason is required
  if (props.requireReason && !reason.value.trim()) {
    hasError.value = true;
    errorMessage.value = "Alasan penutupan wajib diisi";
    return;
  }

  hasError.value = false;
  errorMessage.value = "";
  emit("confirm", reason.value.trim());
};
</script>
