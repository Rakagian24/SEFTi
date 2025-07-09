<template>
  <form @submit.prevent="applyFilter" class="flex gap-2 mb-4">
    <input v-model="filter.nama_bank" type="text" placeholder="Nama Bank" class="input input-bordered" />
    <input v-model="filter.kode_bank" type="text" placeholder="Kode Bank" class="input input-bordered" />
    <button type="submit" class="btn btn-secondary">Filter</button>
    <button type="button" class="btn btn-outline ml-2" @click="resetFilter">Reset</button>
  </form>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
const filter = reactive({ nama_bank: '', kode_bank: '' });

function applyFilter() {
  // Emit filter event ke parent
  emit('filter', { ...filter });
}

function resetFilter() {
  filter.nama_bank = '';
  filter.kode_bank = '';
  emit('reset');
}

const emit = defineEmits(['filter', 'reset']);
</script>
