<script setup lang="ts">
import { watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({ editData: Object });
const emit = defineEmits(["close"]);

const form = useForm({
  kode_bank: "",
  nama_bank: "",
  singkatan: "",
  status: "active",
});

const isEdit = computed(() => !!props.editData && !!props.editData.id);

watch(
  () => props.editData,
  (val) => {
    if (val) {
      form.defaults({
        kode_bank: val.kode_bank || "",
        nama_bank: val.nama_bank || "",
        singkatan: val.singkatan || "",
        status: val.status || "active",
      });
      form.reset();
      form.kode_bank = val.kode_bank || "";
      form.nama_bank = val.nama_bank || "";
      form.singkatan = val.singkatan || "";
      form.status = val.status || "active";
    } else {
      form.defaults({
        kode_bank: "",
        nama_bank: "",
        singkatan: "",
        status: "active",
      });
      form.reset();
    }
  },
  { immediate: true }
);

function submit() {
  if (isEdit.value) {
    form.put(`/banks/${props.editData?.id}`, {
      onSuccess: () => emit("close"),
    });
  } else {
    form.post("/banks", {
      onSuccess: () => emit("close"),
    });
  }
}

function handleReset() {
  form.reset();
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
            {{ isEdit ? "Edit Bank" : "Create Bank" }}
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
          <!-- Nama Bank -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Kode Bank <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.kode_bank"
                type="text"
                placeholder="Masukkan kode Bank"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
              <div v-if="form.errors.kode_bank" class="text-red-500 text-sm">{{ form.errors.kode_bank }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nama Bank <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nama_bank"
                type="text"
                placeholder="Masukkan nama Bank"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
              <div v-if="form.errors.nama_bank" class="text-red-500 text-sm">{{ form.errors.nama_bank }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Singkatan <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.singkatan"
                type="text"
                placeholder="Masukkan singkatan Bank"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                required
              />
              <div v-if="form.errors.singkatan" class="text-red-500 text-sm">{{ form.errors.singkatan }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3"> Status </label>
              <div class="flex gap-6">
                <label class="flex items-center">
                  <input
                    type="radio"
                    value="active"
                    v-model="form.status"
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
                <label class="flex items-center">
                  <input
                    type="radio"
                    value="non-active"
                    v-model="form.status"
                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">Non-Active</span>
                </label>
              </div>
              <div v-if="form.errors.status" class="text-red-500 text-sm">{{ form.errors.status }}</div>
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
              {{ isEdit ? "Update" : "Simpan" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
