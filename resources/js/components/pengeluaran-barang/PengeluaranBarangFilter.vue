<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import CustomSelectFilter from '@/components/ui/CustomSelectFilter.vue';
import DateRangeFilter from '@/components/ui/DateRangeFilter.vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  departments: {
    type: Array as () => Array<{ id: number; name: string }>,
    default: () => [],
  },
  jenisPengeluaran: {
    type: Array as () => Array<{ id: string; name: string }>,
    default: () => [],
  },
  hasSelection: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['reset', 'bulk-delete']);

// Local state (initialized from props.filters for back/forward navigation)
const isFilterOpen = ref(false);
const searchQuery = ref<string>(props.filters?.search || '');
const departmentId = ref<string>((props.filters?.department_id ?? '').toString());
const jenisPengeluaranValue = ref<string>(props.filters?.jenis_pengeluaran || '');
const entriesPerPage = ref<string>((props.filters?.per_page ?? '10').toString());
const localTanggalStart = ref<string>(Array.isArray(props.filters?.date) ? props.filters.date[0] || '' : '');
const localTanggalEnd = ref<string>(Array.isArray(props.filters?.date) ? props.filters.date[1] || '' : '');

// Restore filter open state from localStorage
const savedFilterState = localStorage.getItem('pengeluaranBarangShowFilters');
if (savedFilterState !== null) {
  isFilterOpen.value = savedFilterState === 'true';
}

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

// Immediate apply on other simple filters
watch([departmentId, jenisPengeluaranValue, entriesPerPage], () => {
  applyFilters();
});

function toggleFilter() {
  isFilterOpen.value = !isFilterOpen.value;
  localStorage.setItem('pengeluaranBarangShowFilters', isFilterOpen.value ? 'true' : 'false');
}

function onDateChange(which: 'start' | 'end', value: string) {
  if (which === 'start') localTanggalStart.value = value;
  else localTanggalEnd.value = value;

  applyFilters();
}

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value && searchQuery.value.trim()) params.search = searchQuery.value.trim();
  if (departmentId.value) params.department_id = departmentId.value;
  if (jenisPengeluaranValue.value) params.jenis_pengeluaran = jenisPengeluaranValue.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  const start = localTanggalStart.value;
  const end = localTanggalEnd.value;
  if (start || end) {
    params.date = [start || null, end || null];
  }

  router.get('/pengeluaran-barang', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    },
  });
}

function resetFilters() {
  searchQuery.value = '';
  departmentId.value = '';
  jenisPengeluaranValue.value = '';
  entriesPerPage.value = '10';
  localTanggalStart.value = '';
  localTanggalEnd.value = '';

  emit('reset');
  applyFilters();
}
</script>

<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter toggle + expanded filters -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <!-- Expanded filters -->
          <Transition name="filter-expand">
            <div
              v-if="isFilterOpen"
              class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4"
            >
              <!-- Date Range -->
              <div class="flex-shrink-0">
                <DateRangeFilter
                  :start="localTanggalStart"
                  :end="localTanggalEnd"
                  @update:start="(v: string) => onDateChange('start', v)"
                  @update:end="(v: string) => onDateChange('end', v)"
                />
              </div>

              <!-- Department -->
              <div
                v-if="(departments || []).length !== 1"
                class="flex-shrink-0"
              >
                <CustomSelectFilter
                  :model-value="departmentId"
                  @update:modelValue="(v: string) => (departmentId = v)"
                  :options="[
                    { label: 'Semua Departemen', value: '' },
                    ...departments.map((d: any) => ({
                      label: d.label || d.name,
                      value: (d.value ?? d.id).toString(),
                    })),
                  ]"
                  style="min-width: 12rem"
                />
              </div>

              <!-- Jenis Pengeluaran -->
              <!-- <div class="flex-shrink-0">
                <CustomSelectFilter
                  :model-value="jenisPengeluaranValue"
                  @update:modelValue="(v: string) => (jenisPengeluaranValue = v)"
                  :options="[
                    { label: 'Semua Jenis', value: '' },
                    ...jenisPengeluaran.map((j: any) => ({
                      label: j.name,
                      value: j.id,
                    })),
                  ]"
                  style="min-width: 12rem"
                />
              </div> -->

              <!-- Reset icon button -->
              <button
                @click="resetFilters"
                class="flex-shrink-0 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors duration-150 mt-1"
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

          <!-- Filter toggle -->
          <div class="flex items-center gap-3">
            <div
              class="flex items-center cursor-pointer select-none"
              @click="toggleFilter"
            >
            <!-- Funnel icon -->
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

            <!-- Plus icon with rotation -->
            <span
              :class="
                'inline-block transition-transform duration-300 ml-2 ' +
                (isFilterOpen ? 'rotate-45' : 'rotate-0')
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

            <button
              v-if="props.hasSelection"
              type="button"
              @click.stop="emit('bulk-delete')"
              class="flex items-center gap-1 justify-center px-2 h-8 rounded-md border border-red-300 text-red-600 bg-red-50 hover:bg-red-100 transition-colors duration-150 text-xs font-medium"
              title="Hapus"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-4 h-4"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 7.5h12m-9 3v6m6-6v6M9.75 4.5h4.5A1.75 1.75 0 0116 6.25v.25H8v-.25A1.75 1.75 0 019.75 4.5zM7 6.5h10v11.25A1.75 1.75 0 0115.25 19.5H8.75A1.75 1.75 0 017 17.75V6.5z"
                />
              </svg>
              <span>Hapus</span>
            </button>
          </div>
        </div>

        <!-- RIGHT: Show entries + Search -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0 mt-4">
          <!-- Show entries -->
          <div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <CustomSelectFilter
              :model-value="entriesPerPage"
              @update:modelValue="(v: string) => (entriesPerPage = v)"
              :options="[
                { label: '10', value: '10' },
                { label: '25', value: '25' },
                { label: '50', value: '50' },
                { label: '100', value: '100' },
              ]"
              width="5.5rem"
            />
            <span class="ml-2">entries</span>
          </div>

          <!-- Search -->
          <div class="relative flex-1 min-w-64 max-w-xs">
            <input
              v-model="searchQuery"
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
.filter-expand-enter-active,
.filter-expand-leave-active {
  transition: all 0.25s ease;
}
.filter-expand-enter-from,
.filter-expand-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
