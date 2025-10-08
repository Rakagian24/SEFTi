<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <component
            :is="getIcon(type)"
            :class="[
              'w-5 h-5',
              getIconColor(type)
            ]"
          />
          {{ title }}
        </DialogTitle>
      </DialogHeader>

      <div class="py-4">
        <p class="text-sm text-gray-600">
          {{ message }}
        </p>
      </div>

      <DialogFooter class="flex justify-end gap-3">
        <Button
          v-if="showCancel"
          variant="outline"
          @click="handleCancel"
          class="px-4 py-2"
        >
          {{ cancelText }}
        </Button>
        <Button
          @click="handleConfirm"
          :class="[
            'px-4 py-2',
            getButtonColor(type)
          ]"
        >
          {{ confirmText }}
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
import { AlertCircle, Info, CheckCircle, XCircle } from "lucide-vue-next";

interface Props {
  isOpen: boolean;
  type?: 'info' | 'warning' | 'error' | 'success';
  title?: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  showCancel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  title: 'Informasi',
  confirmText: 'OK',
  cancelText: 'Batal',
  showCancel: false
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'confirm': [];
  'cancel': [];
}>();

function getIcon(type: string) {
  switch (type) {
    case 'success':
      return CheckCircle;
    case 'error':
      return XCircle;
    case 'warning':
      return AlertCircle;
    case 'info':
    default:
      return Info;
  }
}

function getIconColor(type: string) {
  switch (type) {
    case 'success':
      return 'text-green-500';
    case 'error':
      return 'text-red-500';
    case 'warning':
      return 'text-yellow-500';
    case 'info':
    default:
      return 'text-blue-500';
  }
}

function getButtonColor(type: string) {
  switch (type) {
    case 'success':
      return 'bg-green-600 hover:bg-green-700 text-white';
    case 'error':
      return 'bg-red-600 hover:bg-red-700 text-white';
    case 'warning':
      return 'bg-yellow-600 hover:bg-yellow-700 text-white';
    case 'info':
    default:
      return 'bg-blue-600 hover:bg-blue-700 text-white';
  }
}

function handleConfirm() {
  emit('confirm');
  emit('update:open', false);
}

function handleCancel() {
  emit('cancel');
  emit('update:open', false);
}
</script>
