<script setup lang="ts">
import { ref } from 'vue';

defineProps<{ show: boolean }>();
const emit = defineEmits<{
  close: [];
  save: [payload: { kode: string; nama: string; tarif: number; deskripsi?: string }];
}>();

const form = ref({ kode: '', nama: '', tarif: 0, deskripsi: '' });
const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.kode) errors.value.kode = "Kode PPH wajib diisi";
  if (!form.value.nama) errors.value.nama = "Nama PPH wajib diisi";
  if (!form.value.tarif) errors.value.tarif = "Tarif wajib diisi";
  if (form.value.tarif && form.value.tarif > 100) {
    errors.value.tarif = "Tarif PPH tidak boleh lebih dari 100%";
  }
  return Object.keys(errors.value).length === 0;
}

function submit() {
  if (!validate()) return;
  emit('save', { ...form.value });
  form.value = { kode: '', nama: '', tarif: 0, deskripsi: '' };
  errors.value = {};
}

function close() {
  emit('close');
  form.value = { kode: '', nama: '', tarif: 0, deskripsi: '' };
  errors.value = {};
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl relative">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">Tambah PPH</h2>
          <button
            @click="close"
            class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="px-6 py-6">
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Kode PPH -->
          <div class="floating-input">
            <input
              v-model="form.kode"
              id="pph_kode"
              type="text"
              class="floating-input-field"
              placeholder=" "
            />
            <label for="pph_kode" class="floating-label"
              >Kode PPh <span class="text-red-500">*</span></label
            >
            <div v-if="errors.kode" class="text-red-500 text-xs mt-1">
              {{ errors.kode }}
            </div>
          </div>

          <!-- Nama PPH -->
          <div class="floating-input">
            <input
              v-model="form.nama"
              id="pph_nama"
              type="text"
              class="floating-input-field"
              placeholder=" "
            />
            <label for="pph_nama" class="floating-label"
              >Nama PPh <span class="text-red-500">*</span></label
            >
            <div v-if="errors.nama" class="text-red-500 text-xs mt-1">
              {{ errors.nama }}
            </div>
          </div>

          <!-- Tarif Pajak -->
          <div class="floating-input">
            <input
              v-model.number="form.tarif"
              id="pph_tarif"
              type="number"
              step="0.01"
              class="floating-input-field"
              placeholder=" "
            />
            <label for="pph_tarif" class="floating-label"
              >Tarif Pajak (%) <span class="text-red-500">*</span></label
            >
            <div v-if="errors.tarif" class="text-red-500 text-xs mt-1">
              {{ errors.tarif }}
            </div>
          </div>

          <!-- Deskripsi -->
          <div class="floating-input">
            <textarea
              v-model="form.deskripsi"
              id="pph_deskripsi"
              rows="3"
              class="floating-input-field resize-none"
              placeholder=" "
            ></textarea>
            <label for="pph_deskripsi" class="floating-label">Deskripsi</label>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
        <button
          type="button"
          @click="submit"
          class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          <span class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              ></path>
            </svg>
            Simpan
          </span>
        </button>
        <button
          type="button"
          @click="close"
          class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
        >
          Batal
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.floating-input {
  position: relative;
}
.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.3s ease-in-out;
}
.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
}
.floating-input-field:is(textarea) {
  padding-top: 1rem;
  padding-bottom: 1rem;
}
</style>