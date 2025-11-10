<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import CustomSelectFilter from '../ui/CustomSelectFilter.vue';
import DateRangeFilter from '../ui/DateRangeFilter.vue';

const props = defineProps({
  filters: Object,
  supplierOptions: {
    type: Array,
    default: () => [],
  },
  departmentOptions: {
    type: Array,
    default: () => [],
  },
  entriesPerPage: [String, Number],
});

const emit = defineEmits([
  'change',
  'update:entries-per-page',
  'reset',
]);

const localFilters = ref({ ...props.filters });
const localEntriesPerPage = ref(props.entriesPerPage || 10);
const isResetting = ref(false);

// Convert entriesPerPage to number if it's a string
const entriesPerPageNumber = computed(() => {
  if (typeof localEntriesPerPage.value === 'string') {
    return parseInt(localEntriesPerPage.value) || 10;
  }
  return localEntriesPerPage.value || 10;
});

const showFilters = ref(localStorage.getItem('listBayarShowFilters') !== 'false');

// Watch for external changes
watch(
  () => props.filters,
  (val) => {
    const isReset = !val || (
      (!val.tanggal_start || val.tanggal_start === '') &&
      (!val.tanggal_end || val.tanggal_end === '') &&
      (!val.supplier_id || val.supplier_id === '') &&
      (!val.department_id || val.department_id === '')
    );

    if (isReset && !isResetting.value) {
      localFilters.value = {
        tanggal_start: '',
        tanggal_end: '',
        supplier_id: '',
        department_id: '',
      };
    } else if (!isResetting.value) {
      localFilters.value = { ...val };
    }
  }
);

watch(
  () => props.entriesPerPage,
  (val) => {
    if (isResetting.value) return;
    localEntriesPerPage.value = val || 10;
  }
);

watch(
  () => localFilters.value.tanggal_start,
  () => {
    if (isResetting.value) return;
    emit('change', {
      ...localFilters.value,
      per_page: localEntriesPerPage.value,
    });
  }
);

watch(
  () => localFilters.value.tanggal_end,
  () => {
    if (isResetting.value) return;
    emit('change', {
      ...localFilters.value,
      per_page: localEntriesPerPage.value,
    });
  }
);

watch(
  () => localFilters.value.supplier_id,
  () => {
    if (isResetting.value) return;
    emit('change', {
      ...localFilters.value,
      per_page: localEntriesPerPage.value,
    });
  }
);

watch(
  () => localFilters.value.department_id,
  () => {
    if (isResetting.value) return;
    emit('change', {
      ...localFilters.value,
      per_page: localEntriesPerPage.value,
    });
  }
);

function updateFilter(key: string, value: any) {
  if (isResetting.value) return;

  localFilters.value[key] = value;
  const filterData = {
    ...localFilters.value,
    per_page: localEntriesPerPage.value,
  };

  emit('change', filterData);
  window.dispatchEvent(new CustomEvent('content-changed'));
}

function updateEntriesPerPage(value: number) {
  localEntriesPerPage.value = value;
  emit('update:entries-per-page', value);
  emit('change', {
    ...localFilters.value,
    per_page: value,
  });
  window.dispatchEvent(new CustomEvent('content-changed'));
}

function resetFilters() {
  // Set flag to prevent watchers from triggering
  isResetting.value = true;

  // Reset all local filter values
  localFilters.value = {
    tanggal_start: '',
    tanggal_end: '',
    supplier_id: '',
    department_id: '',
  };
  localEntriesPerPage.value = 10;

  // Reset the flag after a short delay to allow the reset to complete
  setTimeout(() => {
    isResetting.value = false;
  }, 100);

  emit('reset');
  window.dispatchEvent(new CustomEvent('content-changed'));

  // Also emit change event with reset values to ensure parent component gets updated
  const resetFilterData = {
    ...localFilters.value,
    per_page: localEntriesPerPage.value,
  };
  emit('change', resetFilterData);
}

function toggleFilters() {
  showFilters.value = !showFilters.value;
  localStorage.setItem('listBayarShowFilters', showFilters.value ? 'true' : 'false');
}

// Dropdown options untuk supplier
const supplierFilterOptions = computed(() => {
  return [
    { label: 'Semua Supplier', value: '' },
    ...(props.supplierOptions || []).map((opt: any) => ({
      label: opt.label,
      value: String(opt.value),
    })),
  ];
});

// Dropdown options untuk department
const departmentFilterOptions = computed(() => {
  return [
    { label: 'Semua Departemen', value: '' },
    ...(props.departmentOptions || []).map((opt: any) => ({
      label: opt.label,
      value: String(opt.value),
    })),
  ];
});
</script>

<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200 relative z-50">
    <div class="px-6 py-4">
      <div class="flex gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter Button & Dropdown -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <!-- Filter Dropdowns (when expanded) - POSITIONED ABOVE -->
          <Transition name="filter-expand">
            <div
              v-if="showFilters"
              class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4"
            >
              <!-- Date Range Filter -->
              <div class="flex-shrink-0">
                <DateRangeFilter
                  :start="localFilters.tanggal_start"
                  :end="localFilters.tanggal_end"
                  @update:start="(val: string) => updateFilter('tanggal_start', val)"
                  @update:end="(val: string) => updateFilter('tanggal_end', val)"
                />
              </div>

              <!-- Supplier Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="localFilters.supplier_id ?? ''"
                  @update:modelValue="(value) => updateFilter('supplier_id', value)"
                  :options="supplierFilterOptions"
                  placeholder="Supplier"
                  style="min-width: 12rem"
                />
              </div>

              <!-- Department Filter -->
              <div class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="localFilters.department_id ?? ''"
                  @update:modelValue="(value) => updateFilter('department_id', value)"
                  :options="departmentFilterOptions"
                  placeholder="Departemen"
                  style="min-width: 12rem"
                />
              </div>

              <!-- Reset Icon Button -->
              <button
                @click="resetFilters"
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
          </Transition>

          <!-- Filter Button -->
          <div
            class="flex items-center cursor-pointer select-none"
            @click="toggleFilters"
          >
            <!-- Funnel Icon -->
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

            <!-- Plus Icon with Animation -->
            <span
              :class="
                'inline-block transition-transform duration-300 ml-2 ' +
                (showFilters ? 'rotate-45' : 'rotate-0')
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
        </div>

        <!-- RIGHT: Show entries -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0 mt-4">
          <!-- Show entries per page -->
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
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animasi untuk ikon plus */
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
}

/* Animasi untuk expand ke atas */
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
  padding-bottom: 1rem;
}

/* Custom style for inputs to match the design */
input[type="date"],
input[type="text"] {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: #fff;
  transition: box-shadow 0.2s, border-color 0.2s;
}

input[type="date"]:focus,
input[type="text"]:focus {
  border-color: #5856d6;
  box-shadow: 0 0 0 2px rgba(88, 86, 214, 0.2);
}
</style>
