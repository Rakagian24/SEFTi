<script setup lang="ts">
import { ref, watch, computed } from "vue";
import CustomSelectFilter from "../ui/CustomSelectFilter.vue";
import DateRangeFilter from "../ui/DateRangeFilter.vue";

const props = defineProps({
  filters: Object,
  bankAccounts: {
    type: Array,
    default: () => [],
  },
  entriesPerPage: [String, Number],
  search: String,
});

const emit = defineEmits(["change", "update:search"]);

const localFilters = ref({ ...props.filters });
const localEntriesPerPage = ref(props.entriesPerPage || 10);
// Hapus localSearch dan watcher terkait

// Convert entriesPerPage to number if it's a string
const entriesPerPageNumber = computed(() => {
  if (typeof localEntriesPerPage.value === "string") {
    return parseInt(localEntriesPerPage.value) || 10;
  }
  return localEntriesPerPage.value || 10;
});

const showFilters = ref(localStorage.getItem('bankMasukShowFilters') === 'true');

watch(
  () => props.filters,
  (val) => {
    localFilters.value = { ...val };
  }
);
watch(
  () => props.entriesPerPage,
  (val) => {
    localEntriesPerPage.value = val || 10;
  }
);

watch(() => localFilters.value.start, () => {
  emit('change', {
    ...localFilters.value,
    entriesPerPage: localEntriesPerPage.value,
    search: props.search,
  });
});
watch(() => localFilters.value.end, () => {
  emit('change', {
    ...localFilters.value,
    entriesPerPage: localEntriesPerPage.value,
    search: props.search,
  });
});

function updateFilter(key: string, value: any) {
  localFilters.value[key] = value;
  emit("change", {
    ...localFilters.value,
    entriesPerPage: localEntriesPerPage.value,
    search: props.search,
  });
  window.dispatchEvent(new CustomEvent("content-changed"));
}

function updateEntriesPerPage(value: number) {
  localEntriesPerPage.value = value;
  emit("change", {
    ...localFilters.value,
    entriesPerPage: value,
    search: props.search,
  });
  window.dispatchEvent(new CustomEvent("content-changed"));
}

function resetFilters() {
  localFilters.value = {
    start: '',
    end: '',
    no_bm: '',
    no_pv: '',
    bank_account_id: '',
    terima_dari: '',
  };
  localEntriesPerPage.value = 10;
  // Hapus localSearch.value = '';
  emit('change', {
    entriesPerPage: 10,
    search: '',
    page: 1,
    start: '',
    end: '',
    no_bm: '',
    no_pv: '',
    bank_account_id: '',
    terima_dari: '',
  });
  window.dispatchEvent(new CustomEvent('content-changed'));
  // Jangan ubah showFilters di sini, biarkan tetap expanded
}

function toggleFilters() {
  showFilters.value = !showFilters.value;
  localStorage.setItem('bankMasukShowFilters', showFilters.value ? 'true' : 'false');
}

// Dropdown options untuk bank account (nama pemilik)
const bankAccountOptions = computed(() => {
  return (props.bankAccounts || []).map((acc: any) => ({
    label: acc.nama_pemilik,
    value: String(acc.id),
  }));
});

function onSearchInput(e: Event) {
  emit('update:search', (e.target as HTMLInputElement).value);
}
</script>

<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter Button & Dropdown -->
        <div class="flex flex-col items-start gap-0 flex-1 min-w-0">
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

          <!-- Filter Dropdowns (when expanded) -->
          <div
            v-if="showFilters"
            class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full"
          >
            <!-- Date Range Filter -->
            <div class="flex-shrink-0">
              <DateRangeFilter
                :start="localFilters.start"
                :end="localFilters.end"
                @update:start="(val: string) => updateFilter('start', val)"
                @update:end="(val: string) => updateFilter('end', val)"
              />
            </div>

            <!-- No. BM Filter -->
            <div class="flex-shrink-0">
              <input
                type="text"
                v-model="localFilters.no_bm"
                @input="updateFilter('no_bm', localFilters.no_bm)"
                class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
                placeholder="No. BM"
                style="min-width: 12rem; padding-left: 0.75rem; padding-right: 0.75rem"
              />
            </div>

            <!-- No. PV Filter -->
            <div class="flex-shrink-0">
              <input
                type="text"
                v-model="localFilters.no_pv"
                @input="updateFilter('no_pv', localFilters.no_pv)"
                class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
                placeholder="No. PV"
                style="min-width: 12rem; padding-left: 0.75rem; padding-right: 0.75rem"
              />
            </div>

            <!-- Bank Account Filter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="localFilters.bank_account_id ?? ''"
                @update:modelValue="(value) => updateFilter('bank_account_id', value)"
                :options="[{ label: 'Semua Departemen', value: '' }, ...bankAccountOptions]"
                placeholder="Departemen"
                style="min-width: 12rem"
              />
            </div>
            <!-- Terima Dari Filter -->
            <div class="flex-shrink-0">
              <CustomSelectFilter
                :model-value="localFilters.terima_dari ?? ''"
                @update:modelValue="(value) => updateFilter('terima_dari', value)"
                :options="[
                  { label: 'Semua Tipe', value: '' },
                  { label: 'Customer', value: 'Customer' },
                  { label: 'Karyawan', value: 'Karyawan' },
                  { label: 'Penjualan Toko', value: 'Penjualan Toko' },
                  { label: 'Lainnya', value: 'Lainnya' },
                ]"
                placeholder="Terima Dari"
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
        </div>

        <!-- RIGHT: Show entries & Search -->
        <div class="flex items-center gap-4 flex-wrap flex-shrink-0">
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
                style="min-width: 5.5rem"
              />
            </div>
            <span class="ml-2">entries</span>
          </div>

          <!-- Search -->
          <div class="relative flex-1 min-w-64">
            <input
              :value="search"
              @input="onSearchInput"
              type="text"
              placeholder="Search..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
            />
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
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
/* Animasi untuk ikon plus */
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
}

/* Smooth transition untuk filter dropdown */
.filter-dropdown-enter-active,
.filter-dropdown-leave-active {
  transition: all 0.3s ease;
}
.filter-dropdown-enter-from,
.filter-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive filter layout */
@media (max-width: 768px) {
  .filter-dropdowns {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-dropdowns > div {
    width: 100%;
  }
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

.date-range-filter {
  display: flex;
  align-items: center;
  background: #f7f8fa;
  border-radius: 9999px;
  padding: 0.5rem 1rem;
  gap: 0.5rem;
  border: 1px solid #e5e7eb;
  width: fit-content;
}
.date-input {
  border: none;
  background: transparent;
  outline: none;
  min-width: 110px;
  text-align: center;
}
.calendar-icon {
  margin-left: 0.5rem;
  color: #888;
  display: flex;
  align-items: center;
}
</style>
