<template>
  <div v-if="visible" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
      <button class="absolute top-2 right-2 text-gray-500" @click="$emit('close')">
        <i class="fa fa-times"></i>
      </button>
      <h2 class="text-lg font-bold mb-4">{{ isEdit ? 'Edit Bank' : 'Tambah Bank' }}</h2>
      <form @submit.prevent="onSubmit">
        <div class="mb-3">
          <label class="block mb-1">Kode Bank</label>
          <input v-model="form.kode_bank" :disabled="isEdit" type="text" class="input input-bordered w-full" />
          <div v-if="errors.kode_bank" class="text-red-500 text-sm">{{ errors.kode_bank }}</div>
        </div>
        <div class="mb-3">
          <label class="block mb-1">Nama Bank</label>
          <input v-model="form.nama_bank" type="text" class="input input-bordered w-full" />
          <div v-if="errors.nama_bank" class="text-red-500 text-sm">{{ errors.nama_bank }}</div>
        </div>
        <div class="mb-3">
          <label class="block mb-1">Singkatan</label>
          <input v-model="form.singkatan" type="text" class="input input-bordered w-full" />
        </div>
        <div class="mb-3">
          <label class="block mb-1">Status</label>
          <select v-model="form.status" class="input input-bordered w-full">
            <option value="active">Aktif</option>
            <option value="non-active">Non-Active</option>
          </select>
        </div>
        <div class="flex justify-end mt-4">
          <button type="button" class="btn btn-outline mr-2" @click="$emit('close')">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';

const props = defineProps<{
  modelValue: any | null,
  visible: boolean
}>();
const emit = defineEmits(['save', 'close']);

const form = ref({ kode_bank: '', nama_bank: '', singkatan: '', status: 'active' });
const errors = ref<{ [key: string]: string }>({});

const isEdit = computed(() => !!props.modelValue && !!props.modelValue.id);

watch(() => props.modelValue, (val) => {
  if (val) {
    form.value = { ...val };
  } else {
    form.value = { kode_bank: '', nama_bank: '', singkatan: '', status: 'active' };
  }
}, { immediate: true });

function validate() {
  errors.value = {};
  if (!form.value.kode_bank) errors.value.kode_bank = 'Kode bank wajib diisi';
  if (!form.value.nama_bank) errors.value.nama_bank = 'Nama bank wajib diisi';
  return Object.keys(errors.value).length === 0;
}

function onSubmit() {
  if (!validate()) return;
  emit('save', { ...form.value });
}
</script>
