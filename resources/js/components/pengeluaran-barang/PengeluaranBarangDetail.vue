<script setup lang="ts">
import { formatDate, formatNumber } from '@/lib/formatters';
import { X } from 'lucide-vue-next';

defineProps({
  pengeluaranBarang: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);

function closeModal() {
  emit('close');
}
</script>

<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex justify-between items-center p-6 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800">Detail Pengeluaran Barang</h2>
        <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
          <X class="h-6 w-6" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <p class="text-sm text-gray-500 mb-1">No. Pengeluaran</p>
            <p class="font-medium">{{ pengeluaranBarang.no_pengeluaran }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500 mb-1">Tanggal</p>
            <p class="font-medium">{{ formatDate(pengeluaranBarang.tanggal) }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500 mb-1">Departemen</p>
            <p class="font-medium">{{ pengeluaranBarang.department?.name || '-' }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500 mb-1">Jenis Pengeluaran</p>
            <p class="font-medium">{{ pengeluaranBarang.jenis_pengeluaran }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500 mb-1">Dikeluarkan Oleh</p>
            <p class="font-medium">{{ pengeluaranBarang.createdBy?.name || '-' }}</p>
          </div>

          <div v-if="pengeluaranBarang.keterangan">
            <p class="text-sm text-gray-500 mb-1">Keterangan</p>
            <p class="font-medium">{{ pengeluaranBarang.keterangan }}</p>
          </div>
        </div>

        <!-- Items Table -->
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-800 mb-4">Detail Barang</h3>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Keluar</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(item, index) in pengeluaranBarang.items" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.barang?.nama_barang || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.barang?.satuan || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatNumber(item.qty) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.keterangan || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
        <button
          @click="closeModal"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar { width: 8px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
