<script setup lang="ts">
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import DatePicker from '@/components/ui/DatePicker.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({
      no_bk: '',
      no_pv: '',
      department_id: '',
      supplier_id: '',
      start: '',
      end: '',
      search: '',
    }),
  },
  departments: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['filter']);

const form = ref({
  no_bk: props.filters.no_bk || '',
  no_pv: props.filters.no_pv || '',
  department_id: props.filters.department_id || '',
  supplier_id: props.filters.supplier_id || '',
  start: props.filters.start || '',
  end: props.filters.end || '',
  search: props.filters.search || '',
});

const showFilter = ref(false);

// Watch for changes in form values and emit filter event
watch(
  form,
  debounce((newForm) => {
    emit('filter', newForm);
  }, 500),
  { deep: true }
);

function resetFilter() {
  form.value = {
    no_bk: '',
    no_pv: '',
    department_id: '',
    supplier_id: '',
    start: '',
    end: '',
    search: '',
  };
  emit('filter', form.value);
}

function toggleFilter() {
  showFilter.value = !showFilter.value;
}

function updateDateRange(range) {
  form.value.start = range.start;
  form.value.end = range.end;
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4">
      <div class="flex items-center justify-between">
        <div class="flex-1 mr-4">
          <div class="relative">
            <input
              v-model="form.search"
              type="text"
              placeholder="Search..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                class="h-5 w-5 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <DatePicker
            :start-date="form.start"
            :end-date="form.end"
            @update:range="updateDateRange"
          />

          <button
            @click="toggleFilter"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg
              class="mr-2 h-4 w-4 text-gray-500"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
              />
            </svg>
            Filter
          </button>

          <button
            @click="resetFilter"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg
              class="mr-2 h-4 w-4 text-gray-500"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            Reset
          </button>
        </div>
      </div>

      <div v-if="showFilter" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label for="no_bk" class="block text-sm font-medium text-gray-700">No. BK</label>
          <input
            id="no_bk"
            v-model="form.no_bk"
            type="text"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>

        <div>
          <label for="no_pv" class="block text-sm font-medium text-gray-700">No. PV</label>
          <input
            id="no_pv"
            v-model="form.no_pv"
            type="text"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>

        <div>
          <label for="department_id" class="block text-sm font-medium text-gray-700">Departemen</label>
          <select
            id="department_id"
            v-model="form.department_id"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Semua Departemen</option>
            <option v-for="department in departments" :key="department.id" :value="department.id">
              {{ department.name }}
            </option>
          </select>
        </div>

        <div>
          <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
          <select
            id="supplier_id"
            v-model="form.supplier_id"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">Semua Supplier</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
              {{ supplier.nama }}
            </option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>
