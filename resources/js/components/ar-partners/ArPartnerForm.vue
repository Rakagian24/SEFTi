<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { usePage } from "@inertiajs/vue3";
import CustomSelect from "@/components/ui/CustomSelect.vue";


const props = defineProps({
  editData: Object,
  departments: {
    type: Array as () => Array<{ id: number | string; name: string }>,
    default: () => [],
  },
});
const emit = defineEmits(["close"]);

const { addSuccess, addError } = useMessagePanel();

const page = usePage();
const errors = page.props.errors as Record<string, string>;

const form = ref({
  nama_ap: "",
  jenis_ap: "Customer", // Default value
  alamat: "",
  email: "",
  no_telepon: "",
  contact_person: "",
  department_id: "",
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
    router.put(`/ar-partners/${props.editData.id}`, form.value, {
      onSuccess: () => {
        addSuccess('Data Customer berhasil diperbarui');
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError('Gagal memperbarui data Customer');
      }
    });
  } else {
    router.post("/ar-partners", form.value, {
      onSuccess: () => {
        addSuccess('Data Customer berhasil ditambahkan');
        emit("close");
        // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError('Gagal menambahkan data Customer');
      }
    });
  }
}

function handleReset() {
  form.value = {
    nama_ap: "",
    jenis_ap: "Customer",
    alamat: "",
    email: "",
    no_telepon: "",
    contact_person: "",
    department_id: "",
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
            {{ props.editData ? "Edit Customer" : "Create Customer" }}
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

        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Row 1: Nama Customer -->
          <div class="floating-input">
            <input
              v-model="form.nama_ap"
              type="text"
              id="nama_ap"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="nama_ap" class="floating-label">
              Nama Customer<span class="text-red-500">*</span>
            </label>
            <p v-if="errors.nama_ap" class="text-xs text-red-500 mt-1">{{ errors.nama_ap }}</p>
          </div>

          <!-- Departemen -->
          <div class="floating-input">
            <CustomSelect
              :model-value="form.department_id"
              @update:modelValue="(val: string | number) => (form.department_id = String(val))"
              :options="props.departments.map((d: { id: number | string; name: string }) => ({ label: d.name, value: String(d.id) }))"
              placeholder="Pilih Departemen"
            >
              <template #label>
                Departemen<span class="text-red-500">*</span>
              </template>
            </CustomSelect>
            <p v-if="errors.department_id" class="text-xs text-red-500 mt-1">{{ errors.department_id }}</p>
          </div>

          <!-- Jenis Customer (Radio) -->
          <div class="col-span-2">
            <div class="flex items-center gap-8">
              <label class="flex items-center">
                <input
                  type="radio"
                  value="Customer"
                  v-model="form.jenis_ap"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Customer</span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  value="Karyawan"
                  v-model="form.jenis_ap"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Karyawan</span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  value="Penjualan Toko"
                  v-model="form.jenis_ap"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Penjualan Toko</span>
              </label>
            </div>
          </div>

          <!-- Alamat -->
          <div class="floating-input col-span-2">
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
            <p v-if="errors.alamat" class="text-xs text-red-500 mt-1">{{ errors.alamat }}</p>
          </div>

          <!-- Contact Person -->
          <div class="floating-input col-span-2">
            <input
              v-model="form.contact_person"
              type="text"
              id="contact_person"
              class="floating-input-field"
              placeholder=" "
              maxlength="100"
            />
            <label for="contact_person" class="floating-label">
              Contact Person
            </label>
            <p v-if="errors.contact_person" class="text-xs text-red-500 mt-1">{{ errors.contact_person }}</p>
          </div>

          <!-- No Telepon -->
          <div class="floating-input">
            <input
              v-model="form.no_telepon"
              type="tel"
              id="no_telepon"
              class="floating-input-field"
              placeholder=" "
              required
              pattern="[0-9]*"
              inputmode="numeric"
              @input="form.no_telepon = form.no_telepon.replace(/[^0-9]/g, '')"
            />
            <label for="no_telepon" class="floating-label">
              No Telepon<span class="text-red-500">*</span>
            </label>
            <p v-if="errors.no_telepon" class="text-xs text-red-500 mt-1">{{ errors.no_telepon }}</p>
          </div>

          <!-- Email -->
          <div class="floating-input">
            <input
              v-model="form.email"
              type="email"
              id="email"
              class="floating-input-field"
              placeholder=" "
              required
            />
            <label for="email" class="floating-label">
              Email<span class="text-red-500">*</span>
            </label>
            <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
          </div>

          <!-- Action Buttons -->
          <div class="col-span-2 flex justify-start gap-3 pt-6 border-t border-gray-200">
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
</template>

<style scoped>
.floating-input {
  position: relative;
  /* margin-top: 1rem; */
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

/* Hilangkan margin-top pada kolom baris pertama grid */
form.grid > .floating-input:nth-child(1),
form.grid > .floating-input:nth-child(2) {
  margin-top: 0 !important;
}
</style>
