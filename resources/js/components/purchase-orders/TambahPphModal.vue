<script setup lang="ts">
import { ref, watch } from "vue";
const props = defineProps<{ show: boolean }>();
const emit = defineEmits(["submit", "close"]);
const form = ref({ kode: "", nama: "", tarif: 0, deskripsi: "", status: true });
const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.kode) errors.value.kode = "Kode PPH wajib diisi";
  if (!form.value.nama) errors.value.nama = "Nama PPH wajib diisi";
  if (!form.value.tarif) errors.value.tarif = "Tarif wajib diisi";
  return Object.keys(errors.value).length === 0;
}
function submit() {
  if (!validate()) return;
  emit("submit", { ...form.value });
  form.value = { kode: "", nama: "", tarif: 0, deskripsi: "", status: true };
}
function close() {
  emit("close");
  form.value = { kode: "", nama: "", tarif: 0, deskripsi: "", status: true };
}
watch(
  () => props.show,
  (val) => {
    if (!val) form.value = { kode: "", nama: "", tarif: 0, deskripsi: "", status: true };
  }
);
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
  >
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl relative">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">Pajak Penghasilan</h2>
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
          <div>
            <label class="block text-sm text-gray-600 mb-2">
              Kode PPH <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.kode"
              type="text"
              placeholder="Kode PPH"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              :class="{ 'border-red-300 focus:ring-red-500': errors.kode }"
            />
            <div v-if="errors.kode" class="text-red-500 text-xs mt-1">
              {{ errors.kode }}
            </div>
          </div>

          <!-- Nama PPH -->
          <div>
            <label class="block text-sm text-gray-600 mb-2">
              Nama PPH <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.nama"
              type="text"
              placeholder="Nama PPH"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              :class="{ 'border-red-300 focus:ring-red-500': errors.nama }"
            />
            <div v-if="errors.nama" class="text-red-500 text-xs mt-1">
              {{ errors.nama }}
            </div>
          </div>

          <!-- Tarif Pajak -->
          <div>
            <label class="block text-sm text-gray-600 mb-2">
              Tarif Pajak (%) <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="form.tarif"
              type="number"
              min="0"
              max="100"
              step="0.01"
              placeholder="Tarif Pajak (%)"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              :class="{ 'border-red-300 focus:ring-red-500': errors.tarif }"
            />
            <div v-if="errors.tarif" class="text-red-500 text-xs mt-1">
              {{ errors.tarif }}
            </div>
          </div>

          <!-- Deskripsi -->
          <div>
            <label class="block text-sm text-gray-600 mb-2">Deskripsi</label>
            <textarea
              v-model="form.deskripsi"
              rows="3"
              placeholder="Deskripsi"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
            ></textarea>
          </div>

          <!-- Status Toggle -->
          <div class="flex items-center justify-between">
            <label class="text-sm text-gray-600">
              Status <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center">
              <button
                type="button"
                @click="form.status = !form.status"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :class="form.status ? 'bg-blue-500' : 'bg-gray-300'"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                  :class="form.status ? 'translate-x-6' : 'translate-x-1'"
                ></span>
              </button>
            </div>
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
/* Additional custom styles if needed */
</style>
