<script setup lang="ts">
import { ref, watch } from 'vue';
const props = defineProps<{ show: boolean }>();
const emit = defineEmits(['submit', 'close']);
const form = ref({ nama: '', qty: 1, satuan: '', harga: 0 });
const errors = ref<{ [key: string]: string }>({});

function validate() {
  errors.value = {};
  if (!form.value.nama) errors.value.nama = 'Nama barang wajib diisi';
  if (!form.value.qty) errors.value.qty = 'Qty wajib diisi';
  if (!form.value.satuan) errors.value.satuan = 'Satuan wajib diisi';
  if (!form.value.harga) errors.value.harga = 'Harga wajib diisi';
  return Object.keys(errors.value).length === 0;
}
function submit() {
  if (!validate()) return;
  emit('submit', { ...form.value });
  form.value = { nama: '', qty: 1, satuan: '', harga: 0 };
}
function close() {
  emit('close');
  form.value = { nama: '', qty: 1, satuan: '', harga: 0 };
}
watch(() => props.show, (val) => { if (!val) form.value = { nama: '', qty: 1, satuan: '', harga: 0 }; });
</script>
<template>
  <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-md shadow-xl p-6">
      <h2 class="text-lg font-bold mb-4">Tambah Barang</h2>
      <form @submit.prevent="submit" class="space-y-3">
        <div>
          <label>Nama Barang</label>
          <input v-model="form.nama" class="input w-full" />
          <div v-if="errors.nama" class="text-red-500 text-xs">{{ errors.nama }}</div>
        </div>
        <div>
          <label>Qty</label>
          <input v-model.number="form.qty" type="number" min="1" class="input w-full" />
          <div v-if="errors.qty" class="text-red-500 text-xs">{{ errors.qty }}</div>
        </div>
        <div>
          <label>Satuan</label>
          <input v-model="form.satuan" class="input w-full" />
          <div v-if="errors.satuan" class="text-red-500 text-xs">{{ errors.satuan }}</div>
        </div>
        <div>
          <label>Harga</label>
          <input v-model.number="form.harga" type="number" min="0" class="input w-full" />
          <div v-if="errors.harga" class="text-red-500 text-xs">{{ errors.harga }}</div>
        </div>
        <div class="flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">Tambah</button>
          <button type="button" class="btn btn-secondary" @click="close">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>
