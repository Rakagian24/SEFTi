<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PengeluaranBarangGrid from './PengeluaranBarangGrid.vue';
import CustomSelect from '@/components/ui/CustomSelect.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';

const props = defineProps({
  departments: {
    type: Array as () => Array<{id: number, name: string}>,
    default: () => []
  },
  jenisPengeluaran: {
    type: Array as () => Array<{id: string, name: string}>,
    default: () => []
  },
  userDepartments: {
    type: Array as () => Array<{id: number, name: string}>,
    default: () => []
  }
});

// Form data
const form = ref({
  no_pengeluaran: '',  // Will be auto-generated
  tanggal: new Date().toISOString().split('T')[0],
  department_id: '',
  jenis_pengeluaran: 'Produksi',
  keterangan: ''
});

const items = ref<Array<{
  barang_id: number | null,
  barang_name: string,
  stok_tersedia: number,
  satuan: string,
  qty: number | null,
  keterangan: string
}>>([]);

// Errors
const errors = ref<Record<string, string>>({});

// UI state
const isSubmitting = ref(false);
const showConfirmDialog = ref(false);

// Message panel
const { addSuccess, addError, clearAll } = useMessagePanel();

// Format date for display
function formatDate(date: string) {
  if (!date) return "";
  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "2-digit",
  });
}

// Computed display date
const displayTanggal = computed(() => {
  try {
    const hasTanggal = !!(form.value.tanggal && String(form.value.tanggal).trim() !== "");
    const dateSource = hasTanggal ? (form.value.tanggal as any) : new Date().toISOString();
    return formatDate(dateSource);
  } catch {
    return "";
  }
});

// Computed options for select components
const departmentOptions = computed(() => {
  return props.departments.map(dept => ({
    label: dept.name,
    value: String(dept.id)
  }));
});

// const jenisPengeluaranOptions = computed(() => {
//   return props.jenisPengeluaran.map(jenis => ({
//     label: jenis.name,
//     value: jenis.id
//   }));
// });

// Set default department if user has only one
watch(() => props.userDepartments, (newVal) => {
  if (newVal && newVal.length === 1) {
    form.value.department_id = String(newVal[0].id);
  }
}, { immediate: true });

// Validate form before submission
function validateForm() {
  errors.value = {};

  if (!form.value.tanggal) {
    errors.value.tanggal = 'Tanggal harus diisi';
  }

  if (!form.value.department_id) {
    errors.value.department_id = 'Departemen harus dipilih';
  }

  if (items.value.length === 0) {
    errors.value.items = 'Minimal harus ada satu barang';
    return false;
  }

  // Validate each item
  let isValid = true;
  items.value.forEach((item, index) => {
    if (!item.barang_id) {
      errors.value[`items.${index}.barang_id`] = 'Barang harus dipilih';
      isValid = false;
    }

    if (!item.qty) {
      errors.value[`items.${index}.qty`] = 'Qty harus diisi';
      isValid = false;
    } else if (item.qty <= 0) {
      errors.value[`items.${index}.qty`] = 'Qty harus lebih dari 0';
      isValid = false;
    } else if (item.qty > item.stok_tersedia) {
      errors.value[`items.${index}.qty`] = `Qty melebihi stok tersedia (${item.stok_tersedia})`;
      isValid = false;
    }
  });

  return Object.keys(errors.value).length === 0 && isValid;
}

// Submit form
function submitForm() {
  if (!validateForm()) return;
  showConfirmDialog.value = true;
}

function onConfirmSubmit() {
  if (!validateForm()) {
    showConfirmDialog.value = false;
    return;
  }

  const formData = {
    tanggal: form.value.tanggal,
    department_id: form.value.department_id,
    jenis_pengeluaran: form.value.jenis_pengeluaran,
    keterangan: form.value.keterangan,
    items: items.value.map(item => ({
      barang_id: item.barang_id,
      qty: item.qty,
      keterangan: item.keterangan
    }))
  };

  isSubmitting.value = true;
  clearAll();

  router.post('/pengeluaran-barang', formData, {
    onSuccess: () => {
      addSuccess('Pengeluaran barang berhasil disimpan', 'Berhasil');
      router.visit('/pengeluaran-barang');
    },
    onError: (err) => {
      errors.value = err;
      addError('Gagal menyimpan pengeluaran barang', 'Error');
    },
    onFinish: () => {
      isSubmitting.value = false;
      showConfirmDialog.value = false;
    }
  });
}

function onCancelSubmit() {
  showConfirmDialog.value = false;
}

// Cancel and go back
function cancel() {
  router.visit('/pengeluaran-barang');
}

// Grid will start empty; user adds items via selection modal
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="submitForm" novalidate class="space-y-4">
      <!-- Form Layout -->
      <div class="space-y-4">
        <!-- Row 1: No Pengeluaran Barang | Tanggal -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="floating-input">
            <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed">
              {{ form.no_pengeluaran || "Akan di-generate otomatis" }}
            </div>
            <label for="no_pengeluaran" class="floating-label">No. Pengeluaran Barang</label>
          </div>

          <div class="floating-input">
            <input
              type="text"
              :value="displayTanggal"
              id="tanggal"
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              placeholder=" "
              readonly
            />
            <label for="tanggal" class="floating-label">Tanggal</label>
          </div>
        </div>

        <!-- Row 2: Jenis Pengeluaran (radio) -->
        <!-- <div>
          <div class="flex flex-wrap gap-6 items-center">
            <label
              v-for="opt in jenisPengeluaranOptions"
              :key="opt.value"
              class="flex items-center space-x-2 text-sm text-gray-700"
            >
              <input
                type="radio"
                :value="opt.value"
                v-model="form.jenis_pengeluaran"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span>{{ opt.label }}</span>
            </label>
          </div>
          <div v-if="errors.jenis_pengeluaran" class="text-red-500 text-xs mt-1">{{ errors.jenis_pengeluaran }}</div>
        </div> -->

        <!-- Row 3: Department -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <CustomSelect
              :model-value="form.department_id"
              @update:modelValue="(val) => (form.department_id = val as string)"
              :options="departmentOptions"
              placeholder="Pilih Departemen"
              :class="{ 'border-red-500': errors.department_id }"
            >
              <template #label>Departemen<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">{{ errors.department_id }}</div>
          </div>
        </div>
      </div>
    </form>
  </div>

    <!-- Items Table -->
    <div v-if="errors.items" class="text-red-500 text-xs mb-2">{{ errors.items }}</div>

    <PengeluaranBarangGrid
        v-model:items="items"
        :errors="errors"
        :departmentId="form.department_id"
    />

    <ConfirmDialog
      :show="showConfirmDialog"
      message="Simpan Pengeluaran Barang ini?"
      @confirm="onConfirmSubmit"
      @cancel="onCancelSubmit"
    />

    <!-- Form Actions -->
    <div class="flex justify-start gap-3 pt-6 border-t border-gray-200 mt-6">
        <button
          type="button"
          @click="submitForm"
          :disabled="isSubmitting"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
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
          <span v-if="isSubmitting">Menyimpan...</span>
          <span v-else>Simpan</span>
        </button>
        <button
          type="button"
          @click="cancel"
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

</template>

<style scoped>
/* Floating label styles */
.floating-input {
  position: relative;
}

.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  min-height: 48px;
  transition: all 0.3s ease-in-out;
}

.floating-input-field:focus {
  outline: none;
  border-color: #1F9254;
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
  background-color: white;
  padding: 0 0.25rem;
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
