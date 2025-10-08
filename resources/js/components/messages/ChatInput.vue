<template>
  <form class="chat-input" @submit.prevent="send">
    <div class="input-container">
      <button type="button" class="attachment-btn" @click="triggerFileInputClick">
        📎
      </button>
      <input
        type="text"
        v-model="message"
        maxlength="250"
        placeholder="Write your message..."
        class="message-input"
        @keydown.enter.prevent="send"
      />
      <input type="file" ref="fileInput" @change="onFileChange" style="display: none" />
      <button
        type="submit"
        class="send-btn"
        :disabled="!message.trim() && !(file as any).value"
      >
        <span class="send-icon">📤</span>
        Kirim
      </button>
    </div>
    <div v-if="(file as any).value" class="file-preview">
      <span class="file-name">{{ (file as any).value?.name }}</span>
      <button type="button" @click="removeFile" class="remove-file">✕</button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useAlertDialog } from "@/composables/useAlertDialog";

const emit = defineEmits(["send"]);
const { showWarning } = useAlertDialog();

const message = ref("");
const file = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  const f = target.files?.[0];
  if (f && f.size > 5 * 1024 * 1024) {
    showWarning("Ukuran file maksimal 5MB", "Peringatan File");
    target.value = "";
    return;
  }
  file.value = f || null;
}

function removeFile() {
  file.value = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
}

function send() {
  if (!message.value.trim() && !file.value) return;

  if (message.value.length > 250) {
    showWarning("Pesan maksimal 250 karakter", "Peringatan Pesan");
    return;
  }

  emit("send", { message: message.value, file: file.value });
  reset();
}

function reset() {
  message.value = "";
  file.value = null;
}

function triggerFileInputClick() {
  fileInput.value?.click();
}
</script>

<style scoped>
.chat-input {
  padding: 16px 20px;
  border-top: 1px solid #e9ecef;
  background: white;
}

.input-container {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  border-radius: 24px;
  padding: 8px 12px;
  border: 1px solid #e9ecef;
}

.input-container:focus-within {
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.attachment-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  border-radius: 50%;
  font-size: 16px;
  color: #666;
  transition: background-color 0.2s ease;
}

.attachment-btn:hover {
  background: #e9ecef;
}

.message-input {
  flex: 1;
  border: none;
  background: none;
  padding: 8px 12px;
  font-size: 14px;
  outline: none;
  resize: none;
}

.message-input::placeholder {
  color: #999;
}

.send-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background-color 0.2s ease;
}

.send-btn:hover:not(:disabled) {
  background: #0056b3;
}

.send-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.send-icon {
  font-size: 14px;
}

.file-preview {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #e3f2fd;
  padding: 8px 12px;
  border-radius: 8px;
  margin-top: 8px;
}

.file-name {
  font-size: 12px;
  color: #1976d2;
  font-weight: 500;
}

.remove-file {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  font-size: 12px;
}

.remove-file:hover {
  background: rgba(0, 0, 0, 0.1);
}
</style>
