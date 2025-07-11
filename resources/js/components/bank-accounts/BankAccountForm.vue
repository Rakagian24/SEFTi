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
});
const emit = defineEmits(["close"]);

const { addSuccess, addError, clearAll } = useMessagePanel();

const form = ref({
  nama_pemilik: "",
  no_rekening: "",
  bank_id: "",
  status: "active", // Default value
});

const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.nama_pemilik) errors.value.nama_pemilik = "Nama Pemilik wajib diisi";
  if (!form.value.no_rekening) errors.value.no_rekening = "No Rekening wajib diisi";
  if (form.value.no_rekening && /\D/.test(form.value.no_rekening)) errors.value.no_rekening = "No Rekening hanya boleh angka";
  if (!form.value.bank_id) errors.value.bank_id = "Bank wajib dipilih";
  return Object.keys(errors.value).length === 0;
}

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
        nama_pemilik: "",
        no_rekening: "",
        bank_id: "",
        status: "active",
      };
    }
  },
  { immediate: true }
);

function submit() {
  if (!validate()) return;
  if (props.editData) {
    router.put(`/bank-accounts/${props.editData.id}`, form.value, {
      onSuccess: () => {
        clearAll();
        addSuccess("Data bank account berhasil diperbarui");
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        clearAll();
        addError("Gagal memperbarui data bank account");
      },
    });
  } else {
    router.post("/bank-accounts", form.value, {
      onSuccess: () => {
        clearAll();
        addSuccess("Data bank account berhasil ditambahkan");
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        clearAll();
        addError("Gagal menambahkan data bank account");
      },
    });
  }
}

function handleReset() {
  form.value = {
    nama_pemilik: "",
    no_rekening: "",
    bank_id: "",
    status: "active",
  };
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div
      class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl"
    >
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ props.editData ? "Edit Bank Account" : "Create Bank Account" }}
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

        <form @submit.prevent="submit" novalidate class="space-y-6">
          <!-- Row 1: Nama Pemilik -->
          <div class="floating-input">
            <input
              v-model="form.nama_pemilik"
              :class="{'border-red-500': errors.nama_pemilik}"
              type="text"
              id="nama_pemilik"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="nama_pemilik" class="floating-label">
              Nama Pemilik<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.nama_pemilik" class="text-red-500 text-xs mt-1">{{ errors.nama_pemilik }}</div>
          </div>

          <!-- Row 2: No Rekening -->
          <div class="floating-input">
            <input
              v-model="form.no_rekening"
              :class="{'border-red-500': errors.no_rekening}"
              type="text"
              id="no_rekening"
              class="floating-input-field"
              placeholder=" "
              required
              @input="form.no_rekening = form.no_rekening.replace(/\D/g, '')"
            />
            <label for="no_rekening" class="floating-label">
              No Rekening<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.no_rekening" class="text-red-500 text-xs mt-1">{{ errors.no_rekening }}</div>
          </div>

          <!-- Row 3: Bank -->
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
                class="w-5 h-5"
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
                class="w-5 h-5"
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
