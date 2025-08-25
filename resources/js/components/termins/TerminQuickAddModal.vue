<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";
import { useMessagePanel } from "@/composables/useMessagePanel";
import SmartDepartmentSelect from "@/components/ui/SmartDepartmentSelect.vue";

const emit = defineEmits(["close", "created"]);
const props = withDefaults(
  defineProps<{ departmentId?: number | null; departmentOptions?: any[] }>(),
  { departmentOptions: () => [] }
);

const { addSuccess, addError, clearAll } = useMessagePanel();

const form = ref({
  no_referensi: "",
  jumlah_termin: "",
  keterangan: "",
  status: "active",
});
const errors = ref<{ [key: string]: string }>({});
const loading = ref(false);
const loadingPreview = ref(false);
const selectedDepartmentId = ref<number | null>(props.departmentId ?? null);

function validate() {
  errors.value = {};
  if (!form.value.no_referensi) errors.value.no_referensi = "No Referensi wajib diisi";
  if (!form.value.jumlah_termin) errors.value.jumlah_termin = "Jumlah Termin wajib diisi";
  if (
    form.value.jumlah_termin &&
    (isNaN(Number(form.value.jumlah_termin)) || Number(form.value.jumlah_termin) < 1)
  ) {
    errors.value.jumlah_termin = "Jumlah Termin harus berupa angka minimal 1";
  }
  return Object.keys(errors.value).length === 0;
}

// Sync prop changes
watch(
  () => props.departmentId,
  (val) => {
    selectedDepartmentId.value = val ?? null;
  }
);

// Auto preview no_referensi when department is selected
watch(
  () => selectedDepartmentId.value,
  async (deptId) => {
    if (!deptId) {
      form.value.no_referensi = "";
      return;
    }
    try {
      loadingPreview.value = true;
      const res = await axios.post("/termins/preview-number", { department_id: deptId });
      form.value.no_referensi = res?.data?.preview_number || "";
    } catch {
      // silent fail; user can retry by reopening modal
    } finally {
      loadingPreview.value = false;
    }
  },
  { immediate: true }
);

async function submit() {
  if (!validate()) return;
  loading.value = true;
  clearAll();
  try {
    // Use PO-specific endpoint so it returns JSON payload identical to Perihal quick add
    const res = await axios.post("/purchase-orders/add-termin", {
      no_referensi: form.value.no_referensi,
      jumlah_termin: Number(form.value.jumlah_termin),
      keterangan: form.value.keterangan,
      status: form.value.status,
    });
    addSuccess("Termin berhasil ditambahkan");
    // Emit the created termin object so parent can push and select immediately
    emit("created", res?.data?.data || null);
    emit("close");
  } catch (e: any) {
    if (e?.response?.data?.errors) {
      const srvErr = e.response.data.errors;
      Object.keys(srvErr).forEach((k: string) => {
        (errors.value as any)[k] = srvErr[k][0];
      });
    } else {
      addError(e?.response?.data?.message || "Gagal menambahkan termin");
    }
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-md shadow-xl">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">Tambah Termin</h2>
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
          <!-- No Referensi (readonly when departmentId provided) -->
          <div class="floating-input">
            <input
              v-model="form.no_referensi"
              :class="[
                'floating-input-field',
                {
                  'border-red-500': errors.no_referensi,
                  'bg-gray-50 cursor-not-allowed': !!selectedDepartmentId,
                },
              ]"
              :disabled="!!selectedDepartmentId"
              :readonly="!!selectedDepartmentId"
              type="text"
              id="no_referensi"
              placeholder=" "
              required
            />
            <label for="no_referensi" class="floating-label">
              No Referensi<span class="text-red-500">*</span>
            </label>
            <div v-if="loadingPreview" class="text-xs text-gray-500 mt-1">
              Mengambil preview...
            </div>
            <div v-if="errors.no_referensi" class="text-red-500 text-xs mt-1">
              {{ errors.no_referensi }}
            </div>
          </div>
          <!-- Department -->
          <SmartDepartmentSelect
            v-model="(selectedDepartmentId as any)"
            :departments="(props.departmentOptions as any)"
            label="Department"
            :show-label="true"
            :required="true"
          />
          <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
            {{ errors.department_id }}
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
            <div v-if="errors.jumlah_termin" class="text-red-500 text-xs mt-1">
              {{ errors.jumlah_termin }}
            </div>
          </div>

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
            <label for="keterangan" class="floating-label"> Keterangan </label>
            <div v-if="errors.keterangan" class="text-red-500 text-xs mt-1">
              {{ errors.keterangan }}
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
            >
              {{ loading ? "Menyimpan..." : "Simpan" }}
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

/* Hover effects */
.floating-input:hover .floating-input-field {
  border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
  border-color: #1f9254;
}

/* Make sure the label background covers the border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}
</style>
