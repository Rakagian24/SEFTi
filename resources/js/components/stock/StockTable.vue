<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  rows: any[];
  loading?: boolean;
  currentPage: number;
  perPage: number;
  total: number;
}>();

const emit = defineEmits<{
  'change-page': [page: number];
}>();

const from = computed(() => {
  if (props.total === 0) return 0;
  return (props.currentPage - 1) * props.perPage + 1;
});

const to = computed(() => {
  const end = props.currentPage * props.perPage;
  return end > props.total ? props.total : end;
});

const lastPage = computed(() => {
  if (props.perPage <= 0) return 1;
  return Math.max(1, Math.ceil(props.total / props.perPage));
});

function go(page: number) {
  if (page < 1 || page > lastPage.value) return;
  emit('change-page', page);
}
</script>

<template>
  <div class="bg-white rounded-lg shadow">
    <div class="overflow-auto">
      <table class="min-w-full">
        <thead>
          <tr class="bg-gray-50 text-left text-xs text-gray-500">
            <th class="px-4 py-2">No</th>
            <th class="px-4 py-2">Nama Barang</th>
            <th class="px-4 py-2">Jenis</th>
            <th class="px-4 py-2">Satuan</th>
            <th class="px-4 py-2 text-right">Stock</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!loading && rows.length === 0">
            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
              Pilih departemen dan atur tanggal untuk menampilkan data stock.
            </td>
          </tr>
          <tr v-for="(row, idx) in rows" :key="`${row.nama_barang}-${idx}`" class="border-t">
            <td class="px-4 py-2 text-sm text-gray-700">
              {{ from + idx }}
            </td>
            <td class="px-4 py-2 text-sm text-gray-900">
              {{ row.nama_barang || '-' }}
            </td>
            <td class="px-4 py-2 text-sm text-gray-700">
              {{ row.jenis || '-' }}
            </td>
            <td class="px-4 py-2 text-sm text-gray-700">
              {{ row.satuan || '-' }}
            </td>
            <td class="px-4 py-2 text-sm text-right text-gray-900">
              {{ Number(row.stock || 0).toLocaleString('id-ID') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between p-3 border-t text-sm text-gray-600">
      <div>
        Menampilkan
        <span class="font-medium">{{ from === 0 ? 0 : from }}</span>
        -
        <span class="font-medium">{{ to }}</span>
        dari
        <span class="font-medium">{{ total }}</span>
        data
      </div>
      <div class="flex items-center gap-2">
        <button
          class="px-3 py-1 border rounded"
          :disabled="currentPage <= 1"
          @click="go(currentPage - 1)"
        >
          Prev
        </button>
        <div>Hal {{ currentPage }} / {{ lastPage }}</div>
        <button
          class="px-3 py-1 border rounded"
          :disabled="currentPage >= lastPage"
          @click="go(currentPage + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>
