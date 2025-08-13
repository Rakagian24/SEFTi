<script setup lang="ts">
import { ref, watch } from "vue";
const props = defineProps<{ show: boolean }>();
const emit = defineEmits(["submit", "close"]);
const form = ref({ nama: "", qty: 1, satuan: "", harga: 0 });
const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.nama) errors.value.nama = "Nama barang wajib diisi";
  if (!form.value.qty) errors.value.qty = "Qty wajib diisi";
  if (!form.value.satuan) errors.value.satuan = "Satuan wajib diisi";
  if (!form.value.harga) errors.value.harga = "Harga wajib diisi";
  return Object.keys(errors.value).length === 0;
}
function submit() {
  if (!validate()) return;
  emit("submit", { ...form.value });
  form.value = { nama: "", qty: 1, satuan: "", harga: 0 };
}
function close() {
  emit("close");
  form.value = { nama: "", qty: 1, satuan: "", harga: 0 };
}
watch(
  () => props.show,
  (val) => {
    if (!val) form.value = { nama: "", qty: 1, satuan: "", harga: 0 };
  }
);
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
  >
    <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">Detail Barang</h2>
        <button
          @click="close"
          class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-gray-100 text-gray-400 hover:text-gray-600"
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

      <!-- Form Content -->
      <div class="px-6 py-6">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <input
              v-model="form.nama"
              placeholder="Nama Barang*"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
            />
            <div v-if="errors.nama" class="text-red-500 text-xs mt-1">
              {{ errors.nama }}
            </div>
          </div>

          <div>
            <input
              v-model.number="form.qty"
              type="number"
              min="1"
              placeholder="Qty*"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
            />
            <div v-if="errors.qty" class="text-red-500 text-xs mt-1">
              {{ errors.qty }}
            </div>
          </div>

          <div>
            <input
              v-model="form.satuan"
              placeholder="Satuan*"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
            />
            <div v-if="errors.satuan" class="text-red-500 text-xs mt-1">
              {{ errors.satuan }}
            </div>
          </div>

          <div>
            <input
              v-model.number="form.harga"
              type="number"
              min="0"
              placeholder="Rp. Harga*"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
            />
            <div v-if="errors.harga" class="text-red-500 text-xs mt-1">
              {{ errors.harga }}
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex gap-3 pt-4">
            <button
              type="submit"
              class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                ></path>
              </svg>
              Simpan
            </button>
            <button
              type="button"
              @click="close"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
