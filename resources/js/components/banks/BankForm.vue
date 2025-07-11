<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from "@/composables/useMessagePanel";


const props = defineProps({
  editData: Object,
});
const emit = defineEmits(["close"]);

const { addSuccess, addError } = useMessagePanel();

const form = ref({
  kode_bank: "",
  nama_bank: "",
  singkatan: "",
  status: "active", // Default value
});

// type FormKeys = keyof typeof form.value;

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, val);
    }
  },
  { immediate: true }
);

function submit() {
  if (props.editData) {
    router.put(`/banks/${props.editData.id}`, form.value, {
      onSuccess: () => {
        addSuccess('Data bank berhasil diperbarui');
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError('Gagal memperbarui data bank');
      }
    });
  } else {
    router.post("/banks", form.value, {
      onSuccess: () => {
        addSuccess('Data bank berhasil ditambahkan');
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError('Gagal menambahkan data bank');
      }
    });
  }
}

function handleReset() {
  form.value = {
    kode_bank: "",
    nama_bank: "",
    singkatan: "",
    status: "active",
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
            {{ props.editData ? "Edit Bank" : "Tambah Bank" }}
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

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Row 1: Kode Bank -->
          <div class="floating-input">
            <input
              v-model="form.kode_bank"
              type="text"
              id="kode_bank"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="kode_bank" class="floating-label">
              Kode Bank<span class="text-red-500">*</span>
            </label>
          </div>

          <!-- Row 2: Nama Bank -->
          <div class="floating-input">
            <input
              v-model="form.nama_bank"
              type="text"
              id="nama_bank"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="nama_bank" class="floating-label">
              Nama Bank<span class="text-red-500">*</span>
            </label>
          </div>

          <!-- Row 3: Singkatan -->
          <div class="floating-input">
            <input
              v-model="form.singkatan"
              type="text"
              id="singkatan"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="singkatan" class="floating-label">
              Singkatan<span class="text-red-500">*</span>
            </label>
          </div>

          <!-- Row 4: Status -->
          <div class="mt-4">
            <div class="flex gap-6">
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  class="form-radio text-blue-600"
                  v-model="form.status"
                  value="active"
                  required
                />
                <span class="ml-2">Active</span>
              </label>
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  class="form-radio text-blue-600"
                  v-model="form.status"
                  value="inactive"
                  required
                />
                <span class="ml-2">Inactive</span>
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
              @click="handleReset"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="w-6 h-6"
              >
                <path
                  fill-rule="evenodd"
                  d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                  clip-rule="evenodd"
                />
              </svg>
              Batal
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
