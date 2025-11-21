<script setup lang="ts">
import { ref, watch } from 'vue';
// Using standard HTML elements instead of UI components for compatibility
// import { DateRangePicker } from '@/components/ui/date-range-picker';
// import { Button } from '@/components/ui/button';
// import { Input } from '@/components/ui/input';
// import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, Search, X, Plus } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  },
  departments: {
    type: Array as () => Array<{id: number, name: string}>,
    default: () => []
  },
  jenisPengeluaran: {
    type: Array as () => Array<{id: string, name: string}>,
    default: () => []
  }
});

const emit = defineEmits(['reset', 'export', 'add']);

const searchQuery = ref(props.filters?.search || '');
const departmentId = ref(props.filters?.department_id || '');
const jenisPengeluaranValue = ref(props.filters?.jenis_pengeluaran || '');
const entriesPerPage = ref(props.filters?.per_page || 10);
const dateRange = ref(props.filters?.date || null);

// Watch for changes and emit events
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

watch([departmentId, jenisPengeluaranValue, entriesPerPage], () => {
  applyFilters();
});

function applyFilters() {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (departmentId.value) params.department_id = departmentId.value;
  if (jenisPengeluaranValue.value) params.jenis_pengeluaran = jenisPengeluaranValue.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (dateRange.value && dateRange.value.length === 2) params.date = dateRange.value;

  router.get('/pengeluaran-barang', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function resetFilters() {
  searchQuery.value = '';
  departmentId.value = '';
  jenisPengeluaranValue.value = '';
  entriesPerPage.value = 10;
  dateRange.value = null;
  emit('reset');
  applyFilters();
}

function handleDateRangeChange(range: any) {
  dateRange.value = range;
  applyFilters();
}

function handleExport() {
  emit('export');
}

function handleAdd() {
  emit('add');
}
</script>

<template>
  <div class="bg-white p-6 rounded-t-lg shadow-sm mb-1">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-2 md:mb-0">Filter</h2>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-2" @click="handleExport">
          <Download class="h-4 w-4" />
          <span>Export to Excel</span>
        </button>
        <button type="button" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 flex items-center gap-2" @click="handleAdd">
          <Plus class="h-4 w-4" />
          <span>Add New</span>
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Search -->
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="pl-9 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Date Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
        <div class="flex gap-2">
          <input
            type="date"
            v-model="dateRange[0]"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            @change="handleDateRangeChange(dateRange)"
          />
          <input
            type="date"
            v-model="dateRange[1]"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            @change="handleDateRangeChange(dateRange)"
          />
        </div>
      </div>

      <!-- Department -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
        <select
          v-model="departmentId"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
            {{ dept.name }}
          </option>
        </select>
      </div>

      <!-- Jenis Pengeluaran -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengeluaran</label>
        <select
          v-model="jenisPengeluaranValue"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">All</option>
          <option v-for="jenis in jenisPengeluaran" :key="jenis.id" :value="jenis.id">
            {{ jenis.name }}
          </option>
        </select>
      </div>
    </div>

    <div class="flex justify-between items-center mt-6">
      <div class="flex items-center">
        <label class="text-sm text-gray-600 mr-2">Show</label>
        <select
          v-model="entriesPerPage"
          class="w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-sm text-gray-600 ml-2">entries</span>
      </div>
      <button
        type="button"
        @click="resetFilters"
        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-2"
      >
        <X class="h-4 w-4" />
        Reset Filters
      </button>
    </div>
  </div>
</template>
