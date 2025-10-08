<script setup lang="ts">
import { ref } from 'vue';

defineProps<{ show: boolean }>();
const emit = defineEmits<{
  close: [];
  save: [payload: { kode: string; nama: string; tarif: number; deskripsi?: string }];
}>();

const form = ref({ kode: '', nama: '', tarif: 0, deskripsi: '' });

function submit() {
  if (!form.value.kode || !form.value.nama || !form.value.tarif) return;
  emit('save', { ...form.value });
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-4 w-[420px] space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">Tambah PPH</h3>
        <button class="text-gray-500" @click="$emit('close')">✕</button>
      </div>
      <div class="space-y-2">
        <div class="space-y-1">
          <label class="text-xs text-gray-500">Kode PPh</label>
          <input v-model="form.kode" class="border rounded px-2 py-1 w-full" />
        </div>
        <div class="space-y-1">
          <label class="text-xs text-gray-500">Nama PPh</label>
          <input v-model="form.nama" class="border rounded px-2 py-1 w-full" />
        </div>
        <div class="space-y-1">
          <label class="text-xs text-gray-500">Tarif Pajak (%)</label>
          <input type="number" v-model.number="form.tarif" class="border rounded px-2 py-1 w-full" />
        </div>
        <div class="space-y-1">
          <label class="text-xs text-gray-500">Deskripsi (opsional)</label>
          <textarea v-model="form.deskripsi" class="border rounded px-2 py-1 w-full"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button class="px-3 py-2 rounded border" @click="$emit('close')">Batal</button>
        <button class="px-3 py-2 rounded bg-[#5856D6] text-white" @click="submit">Simpan</button>
      </div>
    </div>
  </div>
</template>


