<script setup lang="ts">
import { computed } from 'vue';
import { formatDate, formatNumber } from '@/lib/formatters';

const props = defineProps<{
  pengeluaranBarang: any;
  show: boolean;
}>();

const emit = defineEmits(['close']);

const items = computed(() => props.pengeluaranBarang?.items || []);

function handleClose() {
  emit('close');
}
</script>

<template>
  <div
    v-if="show && pengeluaranBarang"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
  >
    <div class="bg-white rounded-xl shadow-xl max-w-5xl w-full mx-4 overflow-hidden">
      <!-- Header -->
      <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-gray-200">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">
            {{ pengeluaranBarang.no_pengeluaran }}
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            {{ formatDate(pengeluaranBarang.tanggal) }}
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          @click="handleClose"
        >
          <span class="sr-only">Close</span>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Table -->
      <div class="px-6 py-4 overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="text-left text-xs font-semibold text-gray-600 border-b border-gray-200">
              <th class="px-4 py-3 whitespace-nowrap">Nama Barang</th>
              <!-- <th class="px-4 py-3 whitespace-nowrap">Stok Tersedia</th> -->
              <th class="px-4 py-3 whitespace-nowrap">Satuan</th>
              <th class="px-4 py-3 whitespace-nowrap">Qty Keluar</th>
              <th class="px-4 py-3 whitespace-nowrap">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in items"
              :key="item.id || index"
              class="border-b border-gray-100 last:border-0"
            >
              <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                {{ item.barang?.nama_barang || '-' }}
              </td>
              <!-- <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap"> -->
                <!-- Belum ada informasi stok tersedia per item di detail; tampilkan '-' dulu -->
                <!-- - -->
              <!-- </td> -->
              <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                {{ item.barang?.satuan || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                {{ formatNumber(item.qty) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ item.keterangan || '-' }}
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                Tidak ada item pengeluaran.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer (pagination dummy style) -->
      <div class="px-6 pb-4 pt-2 flex items-center justify-between border-t border-gray-200 text-xs text-gray-500">
        <div></div>
        <div class="flex items-center space-x-1">
          <button class="w-8 h-8 flex items-center justify-center rounded bg-gray-900 text-white text-xs">
            1
          </button>
          <button class="w-8 h-8 flex items-center justify-center rounded bg-gray-200 text-gray-600 text-xs">
            2
          </button>
          <button class="w-8 h-8 flex items-center justify-center rounded bg-gray-200 text-gray-600 text-xs">
            3
          </button>
        </div>
        <div></div>
      </div>
    </div>
  </div>
</template>
