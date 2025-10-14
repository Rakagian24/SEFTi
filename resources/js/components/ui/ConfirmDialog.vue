<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.5);">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80">
      <div class="mb-4 text-center text-gray-800">{{ message }}</div>
      <div class="flex justify-end space-x-2">
        <button type="button" @click="$emit('cancel')" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Batal</button>
        <button type="button" @click="$emit('confirm')" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Ya</button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ show: boolean, message: string }>();
defineEmits(['confirm', 'cancel']);
</script>
