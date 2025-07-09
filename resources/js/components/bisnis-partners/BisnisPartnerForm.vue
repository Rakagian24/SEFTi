<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({ editData: Object });
const emit = defineEmits(["close"]);

const form = ref({
  nama_bp: "",
  jenis_bp: "Customer", // Default value
  alamat: "",
  email: "",
  no_telepon: "",
  nama_bank: "",
  nama_rekening: "",
  no_rekening_va: "",
  terms_of_payment: "",
});

// type FormKeys = keyof typeof form.value;

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, val);
    } else {
      form.value = {
        nama_bp: "",
        jenis_bp: "Customer",
        alamat: "",
        email: "",
        no_telepon: "",
        nama_bank: "",
        nama_rekening: "",
        no_rekening_va: "",
        terms_of_payment: "",
      };
    }
  },
  { immediate: true }
);

function submit() {
  if (props.editData) {
    router.put(`/bisnis-partners/${props.editData.id}`, form.value, {
      onSuccess: () => emit("close"),
    });
  } else {
    router.post("/bisnis-partners", form.value, {
      onSuccess: () => emit("close"),
    });
  }
}

function handleReset() {
  form.value = {
    nama_bp: "",
    jenis_bp: "Customer",
    alamat: "",
    email: "",
    no_telepon: "",
    nama_bank: "",
    nama_rekening: "",
    no_rekening_va: "",
    terms_of_payment: "",
  };
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div
      class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl"
    >
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ props.editData ? "Edit Bisnis Partner" : "Create Bisnis Partner" }}
          </h2>
          <button
            @click="emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Nama Bisnis Partner -->

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nama Bisnis Partner <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nama_bp"
                type="text"
                placeholder="Masukkan nama Bisnis Partner"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
            </div>
            <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Tipe Partner
            </label>
            <div class="flex gap-6">
              <label class="flex items-center">
                <input
                  type="radio"
                  value="Customer"
                  v-model="form.jenis_bp"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Customer</span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  value="Karyawan"
                  v-model="form.jenis_bp"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Karyawan</span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  value="Cabang"
                  v-model="form.jenis_bp"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Cabang</span>
              </label>
            </div>
          </div>
          </div>

          <!-- Alamat -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"> Alamat * </label>
            <textarea
              v-model="form.alamat"
              placeholder="Masukkan alamat lengkap"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
              rows="3"
              required
            ></textarea>
          </div>

          <!-- No Telepon and Email -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                No Telepon *
              </label>
              <input
                v-model="form.no_telepon"
                type="tel"
                placeholder="08123456789"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Email *
              </label>
              <input
                type="text"
                v-model="form.email"
                placeholder="customer@example.com"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
            </div>
          </div>

          <!-- Bank Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
           <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Bank *
              </label>
              <select
                v-model="form.nama_bank"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              >
                <option value="">Pilih Bank</option>
                <option value="Bank A">Bank A</option>
                <option value="Bank B">Bank B</option>
                <option value="Bank C">Bank C</option>
                <option value="Bank D">Bank D</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nama Pemilik Bank *
              </label>
              <input
                v-model="form.nama_rekening"
                type="text"
                placeholder="Masukkan nama pemilik bank"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
            </div>
          </div>


          <!-- Terms of Payment -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                No Rekening *
              </label>
              <input
                v-model="form.no_rekening_va"
                type="text"
                placeholder="1234567890"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Terms of Payment *
              </label>
              <select
                v-model="form.terms_of_payment"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              >
                <option value="">Pilih Payment</option>
                <option value="7 Hari">7 Hari</option>
                <option value="15 Hari">15 Hari</option>
                <option value="30 Hari">30 Hari</option>
                <option value="45 Hari">45 Hari</option>
                <option value="60 Hari">60 Hari</option>
                <option value="90 Hari">90 Hari</option>
              </select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="handleReset"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
            >
              Reset
            </button>
            <button
              type="submit"
              class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
              {{ props.editData ? "Update" : "Simpan" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
