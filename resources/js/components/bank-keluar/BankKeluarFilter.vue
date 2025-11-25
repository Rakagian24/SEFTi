<script setup lang="ts">
import { ref, watch } from 'vue';
import DateRangeFilter from '@/components/ui/DateRangeFilter.vue';
import CustomSelectFilter from '@/components/ui/CustomSelectFilter.vue';

interface Filters {
  no_bk: string;
  no_pv: string;
  department_id: string | number | null;
  supplier_id: string | number | null;
  start: string | null;
  end: string | null;
  search: string;
  per_page?: number;
}

interface SimpleOption {
  id: number | string;
  name?: string;
  nama?: string;
}

const props = defineProps<{
  filters: Filters;
  departments: SimpleOption[];
  suppliers: SimpleOption[];
}>();

const emit = defineEmits<{
  (e: 'filter', payload: Filters): void;
}>();

const form = ref<Filters>({
  no_bk: props.filters.no_bk || '',
  no_pv: props.filters.no_pv || '',
  department_id: props.filters.department_id || '',
  supplier_id: props.filters.supplier_id || '',
  start: props.filters.start || '',
  end: props.filters.end || '',
  search: props.filters.search || '',
});

const showFilter = ref(false);

const entriesPerPageNumber = ref(10);

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

// Watch for changes in form values and emit filter event with simple debounce
watch(
  form,
  (newForm: Filters) => {
    if (filterTimeout) {
      clearTimeout(filterTimeout);
    }
    filterTimeout = setTimeout(() => {
      emit('filter', { ...newForm });
    }, 500);
  },
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
  emit('filter', { ...form.value });
}

function toggleFilter() {
  showFilter.value = !showFilter.value;
}

function updateDateRange(range: { start: string | null; end: string | null }) {
  form.value.start = range.start;
  form.value.end = range.end;
}

function updateEntriesPerPage(value: number) {
  entriesPerPageNumber.value = value;
  emit('filter', {
    ...form.value,
    per_page: value,
  });
}
</script>

<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border border-gray-200 relative z-40">
    <div class="px-6 py-4">
      <div class="flex gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter toggle + date range + field filters (expandable) -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <div
            class="flex items-center cursor-pointer select-none mb-3"
            @click="toggleFilter"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="size-6"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"
              />
            </svg>

            <span
              :class="
                'inline-block transition-transform duration-300 ml-2 ' +
                (showFilter ? 'rotate-45' : 'rotate-0')
              "
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="w-4 h-4 text-gray-600"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 4.5v15m7.5-7.5h-15"
                />
              </svg>
            </span>

            <span class="ml-2 text-gray-700 text-sm font-medium">Filter</span>
          </div>

          <transition name="filter-expand">
            <div
              v-if="showFilter"
              class="flex flex-wrap items-center gap-x-4 gap-y-3 max-w-full pb-2"
            >
              <div class="flex-shrink-0">
                <DateRangeFilter
                  :start-date="form.start"
                  :end-date="form.end"
                  @update:range="updateDateRange"
                />
              </div>

              <div class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="form.department_id ?? ''"
                  @update:modelValue="(value) => (form.department_id = value)"
                  :options="[
                    { label: 'Semua Departemen', value: '' },
                    ...departments.map((department) => ({
                      label: department.name ?? department.nama ?? String(department.id),
                      value: department.id,
                    })),
                  ]"
                  placeholder="Departemen"
                  style="min-width: 12rem"
                />
              </div>

              <div class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="form.supplier_id ?? ''"
                  @update:modelValue="(value) => (form.supplier_id = value)"
                  :options="[
                    { label: 'Semua Supplier', value: '' },
                    ...suppliers.map((supplier: any) => ({
                      label:
                        supplier.nama_supplier ??
                        supplier.nama ??
                        supplier.name ??
                        String(supplier.id),
                      value: supplier.id,
                    })),
                  ]"
                  placeholder="Supplier"
                  :searchable="true"
                  style="min-width: 12rem"
                />
              </div>

              <button
                @click="resetFilter"
                class="flex-shrink-0 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors duration-150"
                title="Reset filter"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="size-6"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                  />
                </svg>
              </button>
            </div>
          </transition>
        </div>

        <!-- RIGHT: Search -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0 mt-4">
                    <div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <div class="relative">
              <CustomSelectFilter
                :model-value="entriesPerPageNumber"
                @update:modelValue="updateEntriesPerPage"
                :options="[
                  { label: '10', value: 10 },
                  { label: '25', value: 25 },
                  { label: '50', value: 50 },
                  { label: '100', value: 100 },
                ]"
                width="5.5rem"
              />
            </div>
            <span class="ml-2">entries</span>
          </div>
          <div class="relative min-w-64">
            <input
              v-model="form.search"
              type="text"
              placeholder="Search..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                class="h-4 w-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
}

.filter-expand-enter-active,
.filter-expand-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}
.filter-expand-enter-from,
.filter-expand-leave-to {
  opacity: 0;
  max-height: 0;
  margin-bottom: 0;
  padding-bottom: 0;
}
.filter-expand-enter-to,
.filter-expand-leave-from {
  opacity: 1;
  max-height: 200px;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
}

@media (max-width: 768px) {
  .min-w-64 {
    min-width: 100%;
  }
}
</style>

