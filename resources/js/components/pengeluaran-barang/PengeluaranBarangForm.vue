<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { formatNumber } from '@/lib/formatters';
import { Search, Plus, Trash2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  departments: {
    type: Array as () => Array<{id: number, name: string}>,
    default: () => []
  },
  jenisPengeluaran: {
    type: Array as () => Array<{id: string, name: string}>,
    default: () => []
  },
  userDepartments: {
    type: Array as () => Array<{id: number, name: string}>,
    default: () => []
  }
});

// Form data
const tanggal = ref(new Date().toISOString().split('T')[0]);
const departmentId = ref('');
const jenisPengeluaranValue = ref('Produksi');
const keterangan = ref('');
const items = ref<Array<{
  barang_id: number | null,
  barang_name: string,
  stok_tersedia: number,
  satuan: string,
  qty: number | null,
  keterangan: string
}>>([]);

// Errors
const errors = ref<Record<string, string>>({});

// Set default department if user has only one
watch(() => props.userDepartments, (newVal) => {
  if (newVal && newVal.length === 1) {
    departmentId.value = String(newVal[0].id);
  }
}, { immediate: true });

// Barang search
const searchBarang = ref('');
const barangResults = ref<Array<any>>([]);
const isSearchingBarang = ref(false);
const showBarangResults = ref(false);

// Add empty item row
function addItem() {
  items.value.push({
    barang_id: null,
    barang_name: '',
    stok_tersedia: 0,
    satuan: '',
    qty: null,
    keterangan: ''
  });
}

// Remove item row
function removeItem(index: number) {
  items.value.splice(index, 1);
}

// Search for barang
async function searchBarangs() {
  if (!departmentId.value) {
    errors.value.department_id = 'Pilih departemen terlebih dahulu';
    return;
  }

  if (!searchBarang.value) return;

  isSearchingBarang.value = true;
  showBarangResults.value = true;

  try {
    const response = await axios.get('/pengeluaran-barang/barang-stock', {
      params: {
        department_id: departmentId.value,
        search: searchBarang.value
      }
    });

    barangResults.value = response.data.data;
  } catch (error) {
    console.error('Error searching barang:', error);
  } finally {
    isSearchingBarang.value = false;
  }
}

// Select barang for an item
function selectBarang(barang: any, index: number) {
  items.value[index] = {
    barang_id: barang.id,
    barang_name: barang.nama_barang,
    stok_tersedia: barang.stok_tersedia || 0,
    satuan: barang.satuan || '-',
    qty: null,
    keterangan: ''
  };

  searchBarang.value = '';
  barangResults.value = [];
  showBarangResults.value = false;
}

// Validate form before submission
function validateForm() {
  errors.value = {};

  if (!tanggal.value) {
    errors.value.tanggal = 'Tanggal harus diisi';
  }

  if (!departmentId.value) {
    errors.value.department_id = 'Departemen harus dipilih';
  }

  if (!jenisPengeluaranValue.value) {
    errors.value.jenis_pengeluaran = 'Jenis pengeluaran harus dipilih';
  }

  if (items.value.length === 0) {
    errors.value.items = 'Minimal harus ada satu barang';
    return false;
  }

  // Validate each item
  let isValid = true;
  items.value.forEach((item, index) => {
    if (!item.barang_id) {
      errors.value[`items.${index}.barang_id`] = 'Barang harus dipilih';
      isValid = false;
    }

    if (!item.qty) {
      errors.value[`items.${index}.qty`] = 'Qty harus diisi';
      isValid = false;
    } else if (item.qty <= 0) {
      errors.value[`items.${index}.qty`] = 'Qty harus lebih dari 0';
      isValid = false;
    } else if (item.qty > item.stok_tersedia) {
      errors.value[`items.${index}.qty`] = `Qty melebihi stok tersedia (${item.stok_tersedia})`;
      isValid = false;
    }
  });

  return Object.keys(errors.value).length === 0 && isValid;
}

// Submit form
function submitForm() {
  if (!validateForm()) return;

  const formData = {
    tanggal: tanggal.value,
    department_id: departmentId.value,
    jenis_pengeluaran: jenisPengeluaranValue.value,
    keterangan: keterangan.value,
    items: items.value.map(item => ({
      barang_id: item.barang_id,
      qty: item.qty,
      keterangan: item.keterangan
    }))
  };

  router.post('/pengeluaran-barang', formData, {
    onSuccess: () => {
      router.visit('/pengeluaran-barang');
    },
    onError: (err) => {
      errors.value = err;
    }
  });
}

// Cancel and go back
function cancel() {
  router.visit('/pengeluaran-barang');
}

// Add initial empty item
addItem();
</script>

<template>
  <div class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Create Pengeluaran Barang</h2>

    <form @submit.prevent="submitForm">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Tanggal -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengeluaran</label>
          <input
            v-model="tanggal"
            type="date"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            :class="{ 'border-red-500': errors.tanggal }"
          />
          <p v-if="errors.tanggal" class="mt-1 text-sm text-red-600">{{ errors.tanggal }}</p>
        </div>

        <!-- Department -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
          <select
            v-model="departmentId"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            :class="{ 'border-red-500': errors.department_id }"
          >
            <option value="">Pilih Departemen</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
              {{ dept.name }}
            </option>
          </select>
          <p v-if="errors.department_id" class="mt-1 text-sm text-red-600">{{ errors.department_id }}</p>
        </div>

        <!-- Jenis Pengeluaran -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengeluaran</label>
          <select
            v-model="jenisPengeluaranValue"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            :class="{ 'border-red-500': errors.jenis_pengeluaran }"
          >
            <option v-for="jenis in jenisPengeluaran" :key="jenis.id" :value="jenis.id">
              {{ jenis.name }}
            </option>
          </select>
          <p v-if="errors.jenis_pengeluaran" class="mt-1 text-sm text-red-600">{{ errors.jenis_pengeluaran }}</p>
        </div>

        <!-- Keterangan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
          <textarea
            v-model="keterangan"
            rows="2"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          ></textarea>
        </div>
      </div>

      <!-- Items Table -->
      <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-800">Detail Barang</h3>
          <button
            type="button"
            @click="addItem"
            class="px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-1"
          >
            <Plus class="h-4 w-4" />
            Add Barang
          </button>
        </div>

        <p v-if="errors.items" class="mb-2 text-sm text-red-600">{{ errors.items }}</p>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Tersedia</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Keluar</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(item, index) in items" :key="index">
                <!-- Nama Barang -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="relative">
                    <div v-if="item.barang_id" class="text-sm text-gray-900">
                      {{ item.barang_name }}
                    </div>
                    <div v-else class="flex items-center">
                      <input
                        v-model="searchBarang"
                        @focus="showBarangResults = true"
                        @input="searchBarangs"
                        type="text"
                        placeholder="Search barang..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': errors[`items.${index}.barang_id`] }"
                      />
                      <button
                        type="button"
                        @click="searchBarangs"
                        class="absolute right-2 top-1/2 -translate-y-1/2"
                      >
                        <Search class="h-4 w-4 text-gray-400" />
                      </button>
                    </div>
                    <p v-if="errors[`items.${index}.barang_id`]" class="mt-1 text-sm text-red-600">{{ errors[`items.${index}.barang_id`] }}</p>

                    <!-- Search Results Dropdown -->
                    <div
                      v-if="showBarangResults && barangResults.length > 0 && !item.barang_id"
                      class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 text-sm"
                    >
                      <div
                        v-for="barang in barangResults"
                        :key="barang.id"
                        @click="selectBarang(barang, index)"
                        class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                      >
                        {{ barang.nama_barang }} ({{ barang.satuan || '-' }})
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Stok Tersedia -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatNumber(item.stok_tersedia) }}
                </td>

                <!-- Satuan -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ item.satuan }}
                </td>

                <!-- Qty Keluar -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <input
                    v-model.number="item.qty"
                    type="number"
                    min="0"
                    :max="item.stok_tersedia"
                    class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    :class="{ 'border-red-500': errors[`items.${index}.qty`] }"
                  />
                  <p v-if="errors[`items.${index}.qty`]" class="mt-1 text-sm text-red-600">{{ errors[`items.${index}.qty`] }}</p>
                </td>

                <!-- Keterangan -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <input
                    v-model="item.keterangan"
                    type="text"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </td>

                <!-- Action -->
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <button
                    type="button"
                    @click="removeItem(index)"
                    class="text-red-600 hover:text-red-900"
                  >
                    <Trash2 class="h-5 w-5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="cancel"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Kembali
        </button>
        <button
          type="submit"
          class="px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700"
        >
          Simpan
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
