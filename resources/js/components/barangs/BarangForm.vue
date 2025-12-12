<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from "@/composables/useMessagePanel";
import CustomSelect from "../ui/CustomSelect.vue";
import CustomMultiSelect from "../ui/CustomMultiSelect.vue";

interface JenisOption { id: number; nama_jenis_barang: string; singkatan?: string }

const props = defineProps<{
  editData?: Record<string, any>;
  jenisOptions: JenisOption[];
  departmentOptions?: Array<{ id: number | string; name?: string; label?: string; value?: string | number }>;
}>();
const emit = defineEmits(["close"]);

const { addSuccess, addError, clearAll } = useMessagePanel();

const form = ref({
  nama_barang: "",
  jenis_barang_id: "",
  satuan: "",
  department_ids: [] as string[],
  status: "active",
});

const errors = ref<Record<string, string>>({});

function validate() {
  errors.value = {};
  if (!form.value.nama_barang) errors.value.nama_barang = "Nama Barang wajib diisi.";
  if (!form.value.jenis_barang_id) errors.value.jenis_barang_id = "Jenis Barang wajib dipilih.";
  return Object.keys(errors.value).length === 0;
}

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, val);
      if ((val as any).jenisBarang && (val as any).jenisBarang.id) {
        form.value.jenis_barang_id = String((val as any).jenisBarang.id);
      } else if ((val as any).jenis_barang_id) {
        form.value.jenis_barang_id = String((val as any).jenis_barang_id);
      }
      if ((val as any).departments) {
        form.value.department_ids = (val as any).departments.map((d: any) => d.id.toString());
      } else if ((val as any).department_ids) {
        form.value.department_ids = (val as any).department_ids.map((id: any) => id.toString());
      } else {
        form.value.department_ids = [];
      }
    } else {
      form.value = { nama_barang: "", jenis_barang_id: "", satuan: "", department_ids: [], status: "active" };
    }
  },
  { immediate: true }
);

function submit() {
  if (!validate()) return;
  clearAll();
  if (props.editData) {
    router.put(`/barangs/${props.editData.id}`, form.value, {
      onSuccess: () => {
        addSuccess("Data Barang berhasil diperbarui");
        emit("close");
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: (serverErrors: any) => {
        clearAll();
        errors.value = {};
        if (serverErrors && typeof serverErrors === "object") {
          Object.entries(serverErrors).forEach(([key, val]) => {
            errors.value[key] = Array.isArray(val) ? (val as string[])[0] : (val as string);
          });
          const messages = Object.values(serverErrors as any).flat().join(" ");
          addError(messages || "Gagal memperbarui data");
        } else {
          addError("Gagal memperbarui data");
        }
      },
    });
  } else {
    router.post("/barangs", form.value, {
      onSuccess: () => {
        addSuccess("Data Barang berhasil ditambahkan");
        emit("close");
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: (serverErrors: any) => {
        clearAll();
        errors.value = {};
        if (serverErrors && typeof serverErrors === "object") {
          Object.entries(serverErrors).forEach(([key, val]) => {
            errors.value[key] = Array.isArray(val) ? (val as string[])[0] : (val as string);
          });
          const messages = Object.values(serverErrors as any).flat().join(" ");
          addError(messages || "Gagal menambahkan data");
        } else {
          addError("Gagal menambahkan data");
        }
      },
    });
  }
}

function handleReset() {
  form.value = { nama_barang: "", jenis_barang_id: "", satuan: "", department_ids: [], status: "active" };
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">{{ props.editData ? "Edit Barang" : "Tambah Barang" }}</h2>
          <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="submit" novalidate class="space-y-6">
          <div class="floating-input">
            <input v-model="form.nama_barang" :class="{'border-red-500': errors.nama_barang}" type="text" id="nama_barang" class="floating-input-field" placeholder=" " required />
            <label for="nama_barang" class="floating-label">Nama Barang<span class="text-red-500">*</span></label>
            <div v-if="errors.nama_barang" class="text-red-500 text-xs mt-1">{{ errors.nama_barang }}</div>
          </div>

          <div>
            <CustomSelect :model-value="form.jenis_barang_id ?? ''" @update:modelValue="(val) => (form.jenis_barang_id = val)" :options="props.jenisOptions.map(j => ({ label: j.singkatan ? `${j.nama_jenis_barang} (${j.singkatan})` : j.nama_jenis_barang, value: j.id }))">
              <template #label> Jenis Barang<span class="text-red-500">*</span> </template>
            </CustomSelect>
            <div v-if="errors.jenis_barang_id" class="text-red-500 text-xs mt-1">{{ errors.jenis_barang_id }}</div>
          </div>

          <div class="floating-input">
            <input v-model="form.satuan" :class="{'border-red-500': errors.satuan}" type="text" id="satuan" class="floating-input-field" placeholder=" " />
            <label for="satuan" class="floating-label">Satuan</label>
            <div v-if="errors.satuan" class="text-red-500 text-xs mt-1">{{ errors.satuan }}</div>
          </div>

          <div>
            <CustomMultiSelect
              :model-value="form.department_ids"
              @update:modelValue="(val) => (form.department_ids = val as string[])"
              :options="(props.departmentOptions || []).map((d: any) => ({
                label: d.name ?? d.label,
                value: String(d.value ?? d.id ?? ''),
              }))"
              :searchable="true"
              placeholder="Pilih Department..."
            >
              <template #label>Department</template>
            </CustomMultiSelect>
            <div v-if="errors.department_ids" class="text-red-500 text-xs mt-1">{{ errors.department_ids }}</div>
          </div>

          <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
              <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
              Simpan
            </button>
            <button type="button" @click="handleReset" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
              Reset
            </button>
            <button type="button" @click="emit('close')" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.floating-input { position: relative; margin-top: 1rem; }
.floating-input-field { width: 100%; padding: 1rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; line-height: 1.25rem; background-color: white; transition: all 0.3s ease-in-out; }
.floating-input-field:focus { outline: none; border-color: #1f9254; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
.floating-label { position: absolute; left: 0.75rem; top: 1rem; font-size: 0.875rem; line-height: 1.25rem; color: #9ca3af; transition: all 0.3s ease-in-out; pointer-events: none; transform-origin: left top; background-color: white; padding: 0 0.25rem; z-index: 1; }
.floating-input-field:focus ~ .floating-label, .floating-input-field:not(:placeholder-shown) ~ .floating-label { top: -0.5rem; left: 0.75rem; font-size: 0.75rem; line-height: 1rem; color: #333333; transform: translateY(0) scale(1); }
</style>
