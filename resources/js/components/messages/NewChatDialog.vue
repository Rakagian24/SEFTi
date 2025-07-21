<template>
  <div v-if="show" class="new-chat-dialog">
    <div class="dialog-overlay" @click="$emit('close')"></div>
    <div class="dialog-content">
      <div class="dialog-header">
        <h3>Mulai Percakapan Baru</h3>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="dialog-body">
        <div class="form-group">
          <label for="contact-select">Pilih Kontak</label>
          <select id="contact-select" v-model="toUserId" class="form-select">
            <option value="">Pilih kontak...</option>
            <option v-for="user in contacts" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label for="message-input">Pesan</label>
          <textarea
            id="message-input"
            v-model="message"
            maxlength="250"
            placeholder="Tulis pesan..."
            class="form-textarea"
            rows="3"
          ></textarea>
          <div class="character-count">{{ message.length }}/250</div>
        </div>

        <div class="form-group">
          <label for="file-input">Lampiran (Opsional)</label>
          <div class="file-input-container">
            <input
              type="file"
              id="file-input"
              ref="fileInput"
              @change="onFileChange"
              class="file-input"
            />
            <button
              type="button"
              class="file-btn"
              @click="(fileInput as any).value?.click()"
            >
              📎 Pilih File
            </button>
          </div>
          <div v-if="file" class="file-preview">
            <span class="file-name">{{ file.name }}</span>
            <button type="button" @click="removeFile" class="remove-file">✕</button>
          </div>
        </div>
      </div>

      <div class="dialog-footer">
        <button @click="$emit('close')" class="btn btn-secondary">Batal</button>
        <button @click="send" class="btn btn-primary" :disabled="!canSend">Kirim</button>
      </div>

      <div v-if="error" class="error-message">{{ error }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";

const props = defineProps({
  show: Boolean,
});

const emit = defineEmits(["close", "created"]);

const contacts = ref<{ id: number | string; name: string }[]>([]);
const toUserId = ref("");
const message = ref("");
const file = ref<File | null>(null);
const error = ref("");
const fileInput = ref<HTMLInputElement | null>(null);

const canSend = computed(() => {
  return toUserId.value && (message.value.trim() || file.value);
});

function fetchContacts() {
  fetch("/api/settings/message/available-contacts")
    .then(res => {
      if (!res.ok) throw new Error("Gagal memuat kontak");
      return res.json();
    })
    .then(data => {
      contacts.value = data;
    })
    .catch(() => {
      error.value = "Gagal memuat kontak";
    });
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  const f = target.files?.[0];
  if (f && f.size > 5 * 1024 * 1024) {
    error.value = "Ukuran file maksimal 5MB";
    target.value = "";
    return;
  }
  file.value = f ?? null;
  error.value = "";
}

function removeFile() {
  file.value = null;
  const fileInput = document.getElementById("file-input") as HTMLInputElement | null;
  if (fileInput) {
    fileInput.value = "";
  }
}

function send() {
  error.value = "";

  if (!toUserId.value) {
    error.value = "Pilih kontak terlebih dahulu.";
    return;
  }

  if (message.value.length > 250) {
    error.value = "Pesan maksimal 250 karakter.";
    return;
  }

  const form = new FormData();
  form.append("to_user_id", toUserId.value);
  form.append("message", message.value);
  if (file.value) form.append("attachment", file.value);

  fetch("/api/settings/message", {
    method: "POST",
    body: form
  })
    .then(async res => {
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.message || "Gagal membuat percakapan.");
      }
      return res.json();
    })
    .then(data => {
      emit("created", data);
      reset();
      emit("close");
    })
    .catch(err => {
      error.value = err.message || "Gagal membuat percakapan.";
    });
}

function reset() {
  toUserId.value = "";
  message.value = "";
  file.value = null;
  error.value = "";
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      fetchContacts();
      reset();
    }
  }
);

onMounted(() => {
  if (props.show) {
    fetchContacts();
  }
});
</script>

<style scoped>
.new-chat-dialog {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.dialog-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.dialog-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
}

.dialog-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: #666;
  padding: 4px;
  border-radius: 4px;
}

.close-btn:hover {
  background: white;
}

.dialog-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
}

.form-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  min-height: 80px;
}

.form-textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.character-count {
  text-align: right;
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

.file-input-container {
  position: relative;
}

.file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.file-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  color: #333;
}

.file-btn:hover {
  background: #e9ecef;
}

.file-preview {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #e3f2fd;
  padding: 8px 12px;
  border-radius: 6px;
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

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e9ecef;
}

.btn {
  padding: 8px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-secondary {
  background: white;
  color: #333;
  border: 1px solid #ddd;
}

.btn-secondary:hover {
  background: #e9ecef;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.error-message {
  color: #dc3545;
  font-size: 14px;
  padding: 12px 24px;
  background: #f8d7da;
  border-top: 1px solid #f5c6cb;
  margin: 0;
}
</style>
