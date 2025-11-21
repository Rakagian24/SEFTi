<template>
  <div
    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
    @click.stop
    @submit.stop
  >
    <!-- Prevent form submission from this component -->
    <div @click.stop @submit.stop @keydown.stop>
      <!-- Action buttons -->
      <div class="mb-4 flex gap-1" @click.stop @submit.stop>
        <!-- Tombol Tambah -->
        <button
          type="button"
          class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
          @click="openBarangSelection"
          @click.stop.prevent
          title="Tambah Barang"
        >
          <Plus class="w-4 h-4" />
        </button>

        <!-- Tombol Clear -->
        <button
          type="button"
          class="w-8 h-8 bg-yellow-500 text-white rounded flex items-center justify-center hover:bg-yellow-600 transition-colors"
          @click="clearAll"
          @click.stop.prevent
          :disabled="!items.length"
          title="Clear"
        >
          <Minus class="w-4 h-4" />
        </button>
      </div>

      <!-- Table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 mb-4">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Nama Barang
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Stok Tersedia
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Satuan
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Qty Keluar
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Keterangan
              </th>
              <th class="px-4 py-3 w-16">
                <!-- Action column header -->
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(item, index) in items" :key="index" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900">
                <div class="text-sm text-gray-900">
                  {{ item.barang_name || '-' }}
                </div>
                <p v-if="errors[`items.${index}.barang_id`]" class="mt-1 text-sm text-red-600">{{ errors[`items.${index}.barang_id`] }}</p>
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ formatNumber(item.stok_tersedia) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ item.satuan }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                <input
                  v-model.number="item.qty"
                  type="number"
                  min="0"
                  :max="item.stok_tersedia"
                  class="w-24 px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': errors[`items.${index}.qty`] }"
                />
                <p v-if="errors[`items.${index}.qty`]" class="mt-1 text-sm text-red-600">{{ errors[`items.${index}.qty`] }}</p>
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">
                <input
                  v-model="item.keterangan"
                  type="text"
                  class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </td>
              <td class="px-4 py-3 w-24">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    class="w-6 h-6 bg-red-500 text-white rounded flex items-center justify-center hover:bg-red-600 transition-colors"
                    @click="removeItem(index)"
                    @click.stop.prevent
                    title="Hapus"
                  >
                    <Trash2 class="w-3 h-3" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                Belum ada barang
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Barang Selection Modal -->
    <BarangSelection
      v-model:open="showBarangSelection"
      :barangs="barangResults"
      :noResultsMessage="'Tidak ada barang yang tersedia untuk departemen ini'"
      @search="handleBarangSearch"
      @select="handleBarangSelect"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Plus, Minus, Trash2 } from 'lucide-vue-next';
import { formatNumber } from '@/lib/formatters';
import axios from 'axios';
import BarangSelection from './BarangSelection.vue';

interface Barang {
  id: number;
  nama_barang: string;
  stok_tersedia: number;
  satuan: string;
}

const props = defineProps({
  items: {
    type: Array as () => Array<{
      barang_id: number | null,
      barang_name: string,
      stok_tersedia: number,
      satuan: string,
      qty: number | null,
      keterangan: string
    }>,
    default: () => []
  },
  errors: {
    type: Object as () => Record<string, string>,
    default: () => ({})
  },
  departmentId: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:items']);

// Proxy items via computed to avoid manual deep watch loops
const items = computed({
  get() {
    return props.items;
  },
  set(val) {
    emit('update:items', val);
  }
});
const barangResults = ref<Array<Barang>>([]);
const isSearchingBarang = ref(false);
const showBarangSelection = ref(false);


// Open Barang Selection modal
function openBarangSelection() {
  if (!props.departmentId) {
    alert('Pilih departemen terlebih dahulu');
    return;
  }

  // Load initial barang data
  loadBarangData();
  showBarangSelection.value = true;
}

// Load barang data from API
async function loadBarangData(search = '') {
  isSearchingBarang.value = true;

  try {
    const response = await axios.get('/pengeluaran-barang/barang-stock', {
      params: {
        department_id: props.departmentId,
        search: search
      }
    });

    barangResults.value = response.data.data;
  } catch (error) {
    console.error('Error loading barang data:', error);
  } finally {
    isSearchingBarang.value = false;
  }
}

// Handle barang search from modal
function handleBarangSearch(query: string) {
  loadBarangData(query);
}

// Handle barang selection from modal
function handleBarangSelect(barang: Barang) {
  items.value = [
    ...items.value,
    {
      barang_id: barang.id,
      barang_name: barang.nama_barang,
      stok_tersedia: barang.stok_tersedia || 0,
      satuan: barang.satuan || '-',
      qty: null,
      keterangan: ''
    }
  ];
}

// Remove item row
function removeItem(index: number) {
  items.value = items.value.filter((_, i) => i !== index);
}

// Clear all items
function clearAll() {
  items.value = [];
}

// Expose functions to parent
defineExpose({
  clearAll
});
</script>

<style scoped>
/* Table row hover effect */
tbody tr:hover {
  background-color: #f9fafb;
}

/* Hover effects for interactive elements */
button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Focus states */
input:focus,
select:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
