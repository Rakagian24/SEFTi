<script setup lang="ts">
import { ref, watch } from 'vue';
const props = defineProps<{ show: boolean }>();
const emit = defineEmits(['submit', 'close']);
const form = ref({ kode: '', nama: '', tarif: 0, deskripsi: '' });
const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.kode) errors.value.kode = 'Kode PPH wajib diisi';
  if (!form.value.nama) errors.value.nama = 'Nama PPH wajib diisi';
  if (!form.value.tarif) errors.value.tarif = 'Tarif wajib diisi';
  return Object.keys(errors.value).length === 0;
}
function submit() {
  if (!validate()) return;
  emit('submit', { ...form.value });
  form.value = { kode: '', nama: '', tarif: 0, deskripsi: '' };
}
function close() {
  emit('close');
  form.value = { kode: '', nama: '', tarif: 0, deskripsi: '' };
}
watch(() => props.show, (val) => { if (!val) form.value = { kode: '', nama: '', tarif: 0, deskripsi: '' }; });
</script>
<template>
  <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-md shadow-xl p-6">
      <h2 class="text-lg font-bold mb-4">Tambah PPH</h2>
      <form @submit.prevent="submit" class="space-y-3">
        <div>
          <label>Kode PPH</label>
          <input v-model="form.kode" class="input w-full" />
          <div v-if="errors.kode" class="text-red-500 text-xs">{{ errors.kode }}</div>
        </div>
        <div>
          <label>Nama PPH</label>
          <input v-model="form.nama" class="input w-full" />
          <div v-if="errors.nama" class="text-red-500 text-xs">{{ errors.nama }}</div>
        </div>
        <div>
          <label>Tarif (%)</label>
          <input v-model.number="form.tarif" type="number" min="0" max="100" step="0.01" class="input w-full" />
          <div v-if="errors.tarif" class="text-red-500 text-xs">{{ errors.tarif }}</div>
        </div>
        <div>
          <label>Deskripsi</label>
          <input v-model="form.deskripsi" class="input w-full" />
        </div>
        <div class="flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">Tambah</button>
          <button type="button" class="btn btn-secondary" @click="close">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>
