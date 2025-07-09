<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Master Bank</h1>
    <BankTable
      @add="onAdd"
      @edit="onEdit"
      @delete="onDelete"
    />
    <BankForm
      :visible="showForm"
      :model-value="selectedBank"
      @save="onSave"
      @close="closeForm"
    />
    <div v-if="notif.message" :class="notif.success ? 'text-green-600' : 'text-red-600'" class="mt-4">{{ notif.message }}</div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import BankTable from '../../components/banks/BankTable.vue';
import BankForm from '../../components/banks/BankForm.vue';

const showForm = ref(false);
const selectedBank = ref<any | null>(null);
const notif = ref<{ message: string, success: boolean }>({ message: '', success: true });

function onAdd() {
  selectedBank.value = null;
  showForm.value = true;
}

function onEdit(bank: any) {
  selectedBank.value = bank;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  selectedBank.value = null;
}

async function onSave(bank: any) {
  try {
    if (bank.id) {
      await axios.put(`/banks/${bank.id}`, bank);
      notif.value = { message: 'Data bank berhasil diupdate', success: true };
    } else {
      await axios.post('/banks', bank);
      notif.value = { message: 'Data bank berhasil ditambahkan', success: true };
    }
    showForm.value = false;
    window.location.reload(); // Sederhana: reload untuk refresh tabel
  } catch (e: any) {
    notif.value = { message: e.response?.data?.message || 'Terjadi kesalahan', success: false };
  }
}

async function onDelete(bank: any) {
  if (!confirm('Yakin ingin membatalkan data bank ini?')) return;
  try {
    await axios.delete(`/banks/${bank.id}`);
    notif.value = { message: 'Data bank berhasil dibatalkan', success: true };
    window.location.reload();
  } catch (e: any) {
    notif.value = { message: e.response?.data?.message || 'Terjadi kesalahan', success: false };
  }
}
</script>
