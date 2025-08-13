<template>
  <div class="upload-container">
    <label v-if="label" class="upload-label">
      {{ label }}<span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- File Upload Area -->
    <div
      class="upload-area"
      :class="{ 'upload-area-dragover': isDragOver }"
      @drop="onDrop"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
      @dragenter.prevent
    >
      <!-- File Selection Button -->
      <button
        type="button"
        class="upload-button"
        @click="fileInput?.click()"
      >
        Pilih Berkas...
      </button>

      <!-- Drag & Drop Area -->
      <div class="upload-drag-area">
        <div class="upload-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
          </svg>
        </div>
        <p class="upload-text">{{ dragText }}</p>
      </div>

      <!-- Uploaded File Display -->
      <div v-if="modelValue" class="uploaded-file">
        <div class="file-info">
          <div class="file-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="file-name">{{ modelValue.name }}</span>
        </div>
        <div class="file-actions">
          <button
            type="button"
            class="file-action-btn"
            @click="previewFile"
            title="Lihat file"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.639 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.639 0-8.573-3.007-9.963-7.178Z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
          </button>
          <button
            type="button"
            class="file-action-btn file-action-delete"
            @click="removeFile"
            title="Hapus file"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Information Icon -->
      <div class="upload-info" v-if="showInfo">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
      </div>
    </div>

    <!-- Hidden File Input -->
    <input
      ref="fileInput"
      type="file"
      @change="onFileChange"
      class="hidden"
      :accept="accept"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface Props {
  modelValue?: File | null;
  label?: string;
  required?: boolean;
  accept?: string;
  maxSize?: number; // in bytes
  dragText?: string;
  showInfo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png',
  maxSize: 50 * 1024 * 1024, // 50MB default
  dragText: 'Bawa berkas ke area ini (maks. 50 MB)',
  showInfo: true
});

const emit = defineEmits<{
  'update:modelValue': [value: File | null];
  'error': [message: string];
}>();

const isDragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function validateFile(file: File): boolean {
  if (file.size > props.maxSize) {
    emit('error', `Ukuran file terlalu besar. Maksimal ${Math.round(props.maxSize / (1024 * 1024))} MB.`);
    return false;
  }
  return true;
}

function onFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (files && files.length > 0) {
    const file = files[0];
    if (validateFile(file)) {
      emit('update:modelValue', file);
    }
  }
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  isDragOver.value = false;

  const files = e.dataTransfer?.files;
  if (files && files.length > 0) {
    const file = files[0];
    if (validateFile(file)) {
      emit('update:modelValue', file);
    }
  }
}

function removeFile() {
  emit('update:modelValue', null);
  // Reset the file input
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function previewFile() {
  if (props.modelValue) {
    const url = URL.createObjectURL(props.modelValue);
    window.open(url, '_blank');
  }
}
</script>

<style scoped>
.upload-container {
  position: relative;
}

.upload-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.upload-area {
  position: relative;
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  background-color: #f9fafb;
  padding: 1.5rem;
  min-height: 120px;
  transition: all 0.2s ease-in-out;
}

.upload-area:hover {
  border-color: #9ca3af;
  background-color: #f3f4f6;
}

.upload-area-dragover {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.upload-button {
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  margin-bottom: 1rem;
}

.upload-button:hover {
  background-color: #f9fafb;
  border-color: #9ca3af;
}

.upload-drag-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-height: 80px;
}

.upload-icon {
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.upload-text {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.uploaded-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  padding: 0.75rem;
  margin-top: 1rem;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.file-icon {
  color: #6b7280;
}

.file-name {
  font-size: 0.875rem;
  color: #374151;
  font-weight: 500;
}

.file-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.file-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 0.25rem;
  background-color: transparent;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

.file-action-btn:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.file-action-delete:hover {
  background-color: #fef2f2;
  color: #dc2626;
}

.upload-info {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  color: #6b7280;
  cursor: help;
}

.upload-info:hover {
  color: #374151;
}

.hidden {
  display: none;
}
</style>
