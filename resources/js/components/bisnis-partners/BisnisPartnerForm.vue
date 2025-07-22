<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from "@/composables/useMessagePanel";
import CustomSelect from "../ui/CustomSelect.vue";

interface Bank {
  id: number;
  nama_bank: string;
  singkatan: string;
  status: string;
}

const props = defineProps({
  editData: Object,
  banks: {
    type: Array as () => Bank[],
    default: () => [],
  },
  asModal: {
    type: Boolean,
    default: true
  }
});
const emit = defineEmits(["close"]);

const { addSuccess, addError, clearAll } = useMessagePanel();

const form = ref({
  nama_bp: "",
  jenis_bp: "Karyawan", // Default value
  alamat: "",
  email: "",
  no_telepon: "",
  bank_id: "", // Ganti nama_bank menjadi bank_id
  nama_rekening: "",
  no_rekening_va: "",
  terms_of_payment: "",
});

const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.nama_bp) errors.value.nama_bp = "Nama Bisnis Partner wajib diisi";
  if (!form.value.bank_id) errors.value.bank_id = "Bank wajib dipilih";
  if (!form.value.jenis_bp) errors.value.jenis_bp = "Jenis Bisnis Partner wajib diisi";
  if (!form.value.nama_rekening) errors.value.nama_rekening = "Nama Pemilik Bank wajib diisi";
  if (!form.value.alamat) errors.value.alamat = "Alamat wajib diisi";
  if (!form.value.no_rekening_va) errors.value.no_rekening_va = "No Rekening/VA wajib diisi";
  if (form.value.no_rekening_va && /\D/.test(form.value.no_rekening_va)) errors.value.no_rekening_va = "No Rekening/VA hanya boleh angka";
  // Hapus validasi required untuk no_telepon dan email
  if (form.value.no_telepon && /\D/.test(form.value.no_telepon)) errors.value.no_telepon = "No Telepon hanya boleh angka";
  if (form.value.email && !/^\S+@\S+\.\S+$/.test(form.value.email)) errors.value.email = "Format email tidak valid";
  if (!form.value.terms_of_payment) errors.value.terms_of_payment = "Terms of Payment wajib diisi";
  return Object.keys(errors.value).length === 0;
}

// type FormKeys = keyof typeof form.value;

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, val);
      // Pastikan bank_id terisi jika ada relasi bank
      if (val.bank && val.bank.id) {
        form.value.bank_id = val.bank.id;
      }
    } else {
      form.value = {
        nama_bp: "",
        jenis_bp: "Karyawan",
        alamat: "",
        email: "",
        no_telepon: "",
        bank_id: "",
        nama_rekening: "",
        no_rekening_va: "",
        terms_of_payment: "",
      };
    }
  },
  { immediate: true }
);

function submit() {
  if (!validate()) return;
  if (props.editData) {
    router.put(`/bisnis-partners/${props.editData.id}`, form.value, {
      onSuccess: () => {
        clearAll();
        addSuccess('Data bisnis partner berhasil diperbarui');
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        clearAll();
        addError('Gagal memperbarui data bisnis partner');
      }
    });
  } else {
    router.post("/bisnis-partners", form.value, {
      onSuccess: () => {
        clearAll();
        addSuccess('Data bisnis partner berhasil ditambahkan');
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        clearAll();
        addError('Gagal menambahkan data bisnis partner');
      }
    });
  }
}

function handleReset() {
  form.value = {
    nama_bp: "",
    jenis_bp: "Karyawan",
    alamat: "",
    email: "",
    no_telepon: "",
    bank_id: "",
    nama_rekening: "",
    no_rekening_va: "",
    terms_of_payment: "",
  };
}
</script>

<template>
  <div v-if="asModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
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
        <form @submit.prevent="submit" novalidate class="space-y-4">
          <!-- Row 1: Nama Bisnis Partner and Bank -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.nama_bp"
                :class="{'border-red-500': errors.nama_bp}"
                type="text"
                id="nama_bp"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="nama_bp" class="floating-label">
                Nama Bisnis Partner<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.nama_bp" class="text-red-500 text-xs mt-1">{{ errors.nama_bp }}</div>
            </div>

            <div>
              <CustomSelect
                :model-value="form.bank_id ?? ''"
                @update:modelValue="(val) => (form.bank_id = val)"
                :options="
                  banks.map((bank) => ({
                    label: `${bank.nama_bank} (${bank.singkatan})`,
                    value: bank.id,
                  }))
                "
              >
                <template #label> Bank<span class="text-red-500">*</span> </template>
              </CustomSelect>
              <div v-if="errors.bank_id" class="text-red-500 text-xs mt-1">{{ errors.bank_id }}</div>
            </div>
          </div>

          <!-- Row 2: Radio Buttons and Nama Pemilik Bank -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-center">
              <div class="flex gap-18">
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
                <label class="flex items-center">
                  <input
                    type="radio"
                    value="Customer"
                    v-model="form.jenis_bp"
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">Customer</span>
                </label>
              </div>
              <div v-if="errors.jenis_bp" class="text-red-500 text-xs mt-1">{{ errors.jenis_bp }}</div>
            </div>

            <div class="floating-input">
              <input
                v-model="form.nama_rekening"
                :class="{'border-red-500': errors.nama_rekening}"
                type="text"
                id="nama_rekening"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="nama_rekening" class="floating-label">
                Nama Pemilik Bank<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.nama_rekening" class="text-red-500 text-xs mt-1">{{ errors.nama_rekening }}</div>
            </div>
          </div>

          <!-- Row 3: Alamat and No Rekening -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <textarea
                v-model="form.alamat"
                :class="{'border-red-500': errors.alamat}"
                id="alamat"
                class="floating-input-field resize-none"
                placeholder=" "
                rows="3"
                required
              ></textarea>
              <label for="alamat" class="floating-label">
                Alamat<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.alamat" class="text-red-500 text-xs mt-1">{{ errors.alamat }}</div>
            </div>

            <div class="floating-input">
              <input
                v-model="form.no_rekening_va"
                :class="{'border-red-500': errors.no_rekening_va}"
                type="text"
                id="no_rekening_va"
                class="floating-input-field"
                placeholder=" "
                required
                @input="form.no_rekening_va = form.no_rekening_va.replace(/\D/g, '')"
              />
              <label for="no_rekening_va" class="floating-label">
                No Rekening/VA<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.no_rekening_va" class="text-red-500 text-xs mt-1">{{ errors.no_rekening_va }}</div>
            </div>
          </div>

          <!-- Row 4: No Telepon and Terms of Payment -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.no_telepon"
                :class="{'border-red-500': errors.no_telepon}"
                type="text"
                id="no_telepon"
                class="floating-input-field"
                placeholder=" "
                @input="form.no_telepon = form.no_telepon.replace(/\D/g, '')"
              />
              <label for="no_telepon" class="floating-label">
                No Telepon
              </label>
              <div v-if="errors.no_telepon" class="text-red-500 text-xs mt-1">{{ errors.no_telepon }}</div>
            </div>

            <div>
              <CustomSelect
                :model-value="form.terms_of_payment ?? ''"
                @update:modelValue="(val) => (form.terms_of_payment = val)"
                :options="[
                  { label: '0 Hari', value: '0 Hari' },
                  { label: '7 Hari', value: '7 Hari' },
                  { label: '15 Hari', value: '15 Hari' },
                  { label: '30 Hari', value: '30 Hari' },
                  { label: '45 Hari', value: '45 Hari' },
                  { label: '60 Hari', value: '60 Hari' },
                  { label: '90 Hari', value: '90 Hari' },
                ]"
              >
                <template #label>
                  Terms of Payment<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div v-if="errors.terms_of_payment" class="text-red-500 text-xs mt-1">{{ errors.terms_of_payment }}</div>
            </div>
          </div>

          <!-- Row 5: Email (Full width) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.email"
                :class="{'border-red-500': errors.email}"
                type="email"
                id="email"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="email" class="floating-label">
                Email
              </label>
              <div v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
            <button
              type="submit"
              class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                fill="#E6E6E6"
                height="24"
                viewBox="0 0 24 24"
                width="24"
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
              >
                <path
                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
                />
              </svg>
              Simpan
            </button>
            <button
              type="button"
              @click="handleReset"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
                />
              </svg>
              Reset
            </button>
            <button
              type="button"
              @click="emit('close')"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-6 h-6"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div v-else>
    <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl mx-auto">
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
        <form @submit.prevent="submit" novalidate class="space-y-4">
          <!-- Row 1: Nama Bisnis Partner and Bank -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.nama_bp"
                type="text"
                id="nama_bp"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="nama_bp" class="floating-label">
                Nama Bisnis Partner<span class="text-red-500">*</span>
              </label>
            </div>

            <div>
              <CustomSelect
                :model-value="form.bank_id ?? ''"
                @update:modelValue="(val) => (form.bank_id = val)"
                :options="
                  banks.map((bank) => ({
                    label: `${bank.nama_bank} (${bank.singkatan})`,
                    value: bank.id,
                  }))
                "
              >
                <template #label> Bank<span class="text-red-500">*</span> </template>
              </CustomSelect>
            </div>
          </div>

          <!-- Row 2: Radio Buttons and Nama Pemilik Bank -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-center">
              <div class="flex gap-18">
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

            <div class="floating-input">
              <input
                v-model="form.nama_rekening"
                type="text"
                id="nama_rekening"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="nama_rekening" class="floating-label">
                Nama Pemilik Bank<span class="text-red-500">*</span>
              </label>
            </div>
          </div>

          <!-- Row 3: Alamat and No Rekening -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <textarea
                v-model="form.alamat"
                id="alamat"
                class="floating-input-field resize-none"
                placeholder=" "
                rows="3"
                required
              ></textarea>
              <label for="alamat" class="floating-label">
                Alamat<span class="text-red-500">*</span>
              </label>
            </div>

            <div class="floating-input">
              <input
                v-model="form.no_rekening_va"
                type="text"
                id="no_rekening_va"
                class="floating-input-field"
                placeholder=" "
                required
                @input="form.no_rekening_va = form.no_rekening_va.replace(/\D/g, '')"
              />
              <label for="no_rekening_va" class="floating-label">
                No Rekening/VA<span class="text-red-500">*</span>
              </label>
            </div>
          </div>

          <!-- Row 4: No Telepon and Terms of Payment -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.no_telepon"
                type="text"
                id="no_telepon"
                class="floating-input-field"
                placeholder=" "
                @input="form.no_telepon = form.no_telepon.replace(/\D/g, '')"
              />
              <label for="no_telepon" class="floating-label">
                No Telepon<span class="text-red-500">*</span>
              </label>
            </div>

            <div>
              <CustomSelect
                :model-value="form.terms_of_payment ?? ''"
                @update:modelValue="(val) => (form.terms_of_payment = val)"
                :options="[
                  { label: '0 Hari', value: '0 Hari' },
                  { label: '7 Hari', value: '7 Hari' },
                  { label: '15 Hari', value: '15 Hari' },
                  { label: '30 Hari', value: '30 Hari' },
                  { label: '45 Hari', value: '45 Hari' },
                  { label: '60 Hari', value: '60 Hari' },
                  { label: '90 Hari', value: '90 Hari' },
                ]"
              >
                <template #label>
                  Terms of Payment<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>
          </div>

          <!-- Row 5: Email (Full width) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.email"
                type="email"
                id="email"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="email" class="floating-label">
                Email
              </label>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
            <button
              type="submit"
              class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                fill="#E6E6E6"
                height="24"
                viewBox="0 0 24 24"
                width="24"
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
              >
                <path
                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
                />
              </svg>
              Simpan
            </button>
            <button
              type="button"
              @click="emit('close')"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-6 h-6"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.floating-input {
  position: relative;
  margin-top: 1rem;
}

.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
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

/* When input is focused or has value - label goes to border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Special handling for select - check if it has selected value */
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Textarea specific styles */
.floating-input-field:is(textarea) {
  resize: vertical;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.floating-input-field:is(textarea):focus ~ .floating-label,
.floating-input-field:is(textarea):not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
}

/* Hover effects */
.floating-input:hover .floating-input-field {
  border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
  border-color: #1f9254;
}

/* Make sure the label background covers the border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}
</style>
