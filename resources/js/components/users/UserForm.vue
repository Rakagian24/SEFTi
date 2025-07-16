<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from "@/composables/useMessagePanel";
import CustomSelect from "../ui/CustomSelect.vue";

const props = defineProps({
  editData: Object,
  roles: {
    type: Array as () => Array<{id:number,name:string}>,
    default: () => [],
  },
  departments: {
    type: Array as () => Array<{id:number,name:string}>,
    default: () => [],
  },
  asModal: {
    type: Boolean,
    default: true
  }
});
const emit = defineEmits(["close"]);

const { addSuccess, addError } = useMessagePanel();

const form = ref({
  name: "",
  email: "",
  phone: "",
  role_id: "",
  department_id: "",
});

const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.role_id) errors.value.role_id = "Role wajib dipilih";
  if (!form.value.department_id) errors.value.department_id = "Department wajib dipilih";
  return Object.keys(errors.value).length === 0;
}

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, val);
      if (val.role_id) form.value.role_id = val.role_id.toString();
      if (val.department_id) form.value.department_id = val.department_id.toString();
    } else {
      form.value = {
        name: "",
        email: "",
        phone: "",
        role_id: "",
        department_id: "",
      };
    }
  },
  { immediate: true }
);

function submit() {
  if (!validate()) return;
  if (props.editData) {
    router.put(`/users/${props.editData.id}`, form.value, {
      onSuccess: () => {
        addSuccess('User berhasil diperbarui');
        emit("close");
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError('Gagal memperbarui user');
      }
    });
  }
}

function handleReset() {
  form.value = {
    name: "",
    email: "",
    phone: "",
    role_id: "",
    department_id: "",
  };
}
</script>

<template>
  <div v-if="asModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            Edit User
          </h2>
          <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="submit" novalidate class="space-y-4">
            <div>
              <CustomSelect
                :model-value="form.role_id ?? ''"
                @update:modelValue="(val) => (form.role_id = val)"
                :options="roles.map((role) => ({ label: role.name, value: role.id.toString() }))"
              >
                <template #label> Role<span class="text-red-500">*</span> </template>
              </CustomSelect>
              <div v-if="errors.role_id" class="text-red-500 text-xs mt-1">{{ errors.role_id }}</div>
            </div>
            <div>
              <CustomSelect
                :model-value="form.department_id ?? ''"
                @update:modelValue="(val) => (form.department_id = val)"
                :options="departments.map((d) => ({ label: d.name, value: d.id.toString() }))"
              >
                <template #label> Department<span class="text-red-500">*</span> </template>
              </CustomSelect>
              <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">{{ errors.department_id }}</div>
            </div>
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="handleReset" class="px-4 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">Reset</button>
            <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
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
