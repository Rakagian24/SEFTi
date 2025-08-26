<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from '@/composables/useMessagePanel';
import SmartDepartmentSelect from '@/components/ui/SmartDepartmentSelect.vue';
import axios from 'axios';

const props = defineProps({
  editData: Object,
  asModal: { type: Boolean, default: true },
  departmentOptions: { type: Array, default: () => [] }
});
const emit = defineEmits(["close", "submit"]);
const { addSuccess, addError, clearAll } = useMessagePanel();
const form = ref({ no_referensi: "", jumlah_termin: "", keterangan: "", department_id: null as number | null, status: "active" });
const errors = ref<{ [key: string]: string }>({});
const loading = ref(false);
const generatingPreview = ref(false);

function validate() {
  errors.value = {};
  if (!form.value.no_referensi) errors.value.no_referensi = "No Referensi wajib diisi";
  if (!form.value.jumlah_termin) errors.value.jumlah_termin = "Jumlah Termin wajib diisi";
  if (form.value.jumlah_termin && (isNaN(Number(form.value.jumlah_termin)) || Number(form.value.jumlah_termin) < 1)) {
    errors.value.jumlah_termin = "Jumlah Termin harus berupa angka minimal 1";
  }
  if (!form.value.department_id) errors.value.department_id = "Department wajib diisi";
  return Object.keys(errors.value).length === 0;
}

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, val);
    } else {
      form.value = { no_referensi: "", jumlah_termin: "", keterangan: "", department_id: null, status: "active" };
    }
  },
  { immediate: true }
);

// Watch for department_id changes to generate preview number
watch(
  () => form.value.department_id,
  async (newDepartmentId) => {
    if (newDepartmentId && !props.editData) {
      await generatePreviewNumber();
    }
  }
);

async function generatePreviewNumber() {
  if (!form.value.department_id) return;

  generatingPreview.value = true;
  try {

    const response = await axios.post('/termins/preview-number', {
      department_id: form.value.department_id
    });

    // Handle different possible response structures
    let previewNumber = null;

    if (response.data && response.data.success && response.data.data && response.data.data.preview_number) {
      previewNumber = response.data.data.preview_number;
    } else if (response.data && response.data.preview_number) {
      previewNumber = response.data.preview_number;
    } else if (response.data && response.data.data) {
      previewNumber = response.data.data;
    } else if (typeof response.data === 'string') {
      previewNumber = response.data;
    }

    if (previewNumber) {
      form.value.no_referensi = previewNumber;
    } else {
    }
  } catch (error: any) {
    if (error.response) {
    } else if (error.request) {
    } else {
    }
  } finally {
    generatingPreview.value = false;
  }
}

function submit() {
  if (!validate()) return;
  clearAll(); // Clear any existing messages
  loading.value = true;

  const formData = {
    ...form.value,
    jumlah_termin: Number(form.value.jumlah_termin)
  };


  if (props.editData) {
    // Update existing termin
    router.put(`/termins/${props.editData.id}`, formData, {
      onSuccess: () => {
        addSuccess('Data termin berhasil diperbarui');
        emit('close');
        window.dispatchEvent(new CustomEvent('table-changed'));
      },
      onError: (errors: any) => {
        clearAll();
        // Handle validation errors
        if (errors && typeof errors === 'object') {
          Object.keys(errors).forEach((key: string) => {
            if (formData.hasOwnProperty(key) && Array.isArray(errors[key])) {
              (errors.value as any)[key] = errors[key][0];
            }
          });
        }
        addError('Terjadi kesalahan saat memperbarui data');
      },
      onFinish: () => {
        loading.value = false;
      }
    });
  } else {
    router.post('/termins', formData, {
      onSuccess: () => {
        addSuccess('Data termin berhasil ditambahkan');
        emit('close');
        window.dispatchEvent(new CustomEvent('table-changed'));
      },
      onError: (errors: any) => {
        clearAll();
        // Handle validation errors
        if (errors && typeof errors === 'object') {
          Object.keys(errors).forEach((key: string) => {
            if (formData.hasOwnProperty(key) && Array.isArray(errors[key])) {
              (errors.value as any)[key] = errors[key][0];
            }
          });
        }
        addError('Terjadi kesalahan saat menyimpan data');
      },
      onFinish: () => {
        loading.value = false;
      }
    });
  }
}
function handleReset() {
  form.value = { no_referensi: "", jumlah_termin: "", keterangan: "", department_id: null, status: "active" };
}
</script>

<template>
  <div v-if="asModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ props.editData ? "Edit Termin" : "Tambah Termin" }}
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
          <!-- No Referensi -->
          <div class="floating-input">
            <input
              v-model="form.no_referensi"
              :class="{ 'border-red-500': errors.no_referensi }"
              type="text"
              id="no_referensi"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="no_referensi" class="floating-label">
              No Referensi<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.no_referensi" class="text-red-500 text-xs mt-1">{{ errors.no_referensi }}</div>
          </div>

          <!-- Jumlah Termin -->
          <div class="floating-input">
            <input
              v-model="form.jumlah_termin"
              :class="{ 'border-red-500': errors.jumlah_termin }"
              type="number"
              id="jumlah_termin"
              class="floating-input-field"
              placeholder=" "
              min="1"
              required
            />
            <label for="jumlah_termin" class="floating-label">
              Jumlah Termin<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.jumlah_termin" class="text-red-500 text-xs mt-1">{{ errors.jumlah_termin }}</div>
          </div>

          <!-- Department -->
          <SmartDepartmentSelect
            v-model="form.department_id as any"
            :departments="(props.departmentOptions as any)"
            label="Department"
            :searchable="true"
            :show-label="true"
            :required="false"
          />
          <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">{{ errors.department_id }}</div>

          <!-- Keterangan -->
          <div class="floating-input">
            <textarea
              v-model="form.keterangan"
              :class="{ 'border-red-500': errors.keterangan }"
              id="keterangan"
              class="floating-input-field"
              placeholder=" "
              rows="3"
            ></textarea>
            <label for="keterangan" class="floating-label">
              Keterangan
            </label>
            <div v-if="errors.keterangan" class="text-red-500 text-xs mt-1">{{ errors.keterangan }}</div>
          </div>


          <!-- Action Buttons -->
          <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50"
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
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
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
