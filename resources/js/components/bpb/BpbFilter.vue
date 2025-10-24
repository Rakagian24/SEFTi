<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200 relative z-50">
    <div class="px-6 py-4">
      <div class="flex gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter Button & Dropdown -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">
          <!-- Filter Dropdowns (when expanded) - POSITIONED ABOVE -->
          <Transition name="filter-expand">
            <div
              v-if="isFilterOpen"
              class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 max-w-full pb-4"
            >
              <!-- Date Range Filter -->
              <div class="flex-shrink-0">
                <DateRangeFilter
                  :start="form.tanggal_start"
                  :end="form.tanggal_end"
                  @update:start="(v:string)=>{ form.tanggal_start = v; applyFilter(); }"
                  @update:end="(v:string)=>{ form.tanggal_end = v; applyFilter(); }"
                />
              </div>

              <!-- Department Filter -->
              <div v-if="(departmentOptions || []).length !== 1" class="flex-shrink-0">
                <div class="space-y-1">
                  <CustomSelectFilter
                    :model-value="(form.department_id ?? '').toString()"
                    @update:modelValue="(v:string)=>{ form.department_id = v; form.supplier_id = ''; applyFilter(); }"
                    :options="[{ label: 'Semua Departemen', value: '' }, ...(props.departmentOptions||[]).map(d=>({ label: d.label||d.name, value: (d.value??d.id).toString() }))]"
                    style="min-width: 12rem"
                  />
                </div>
              </div>

              <!-- Status Filter -->
              <div class="flex-shrink-0">
                <div class="space-y-1">
                  <CustomSelectFilter
                    :model-value="form.status"
                    @update:modelValue="(v:string)=>{ form.status = v; applyFilter(); }"
                    :options="[
                      { label: 'Semua Status', value: '' },
                      { label: 'Draft', value: 'Draft' },
                      { label: 'In Progress', value: 'In Progress' },
                      { label: 'Approved', value: 'Approved' },
                      { label: 'Rejected', value: 'Rejected' },
                      { label: 'Canceled', value: 'Canceled' },
                    ]"
                    style="min-width: 12rem"
                  />
                </div>
              </div>

              <!-- Supplier Filter -->
              <div class="flex-shrink-0">
                <div class="space-y-1">
                  <CustomSelectFilter
                    :model-value="(form.supplier_id ?? '').toString()"
                    @update:modelValue="(v:string)=>{ form.supplier_id = v; applyFilter(); }"
                    :options="[{ label: 'Semua Supplier', value: '' }, ...(props.supplierOptions||[]).map(s=>({ label: s.label||s.nama_supplier||s.name, value: (s.value??s.id).toString() }))]"
                    :searchable="true"
                    style="min-width: 12rem"
                  />
                </div>
              </div>

              <!-- Reset Icon Button -->
              <button
                @click="resetFilter"
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

          <!-- Filter Button -->
          <div class="flex items-center cursor-pointer select-none" @click="toggleFilter">
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
        </div>

        <!-- RIGHT: Show entries & Search -->
        <div class="flex items-end gap-4 flex-wrap flex-shrink-0 mt-4">
          <!-- Show entries per page -->
          <div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <div class="relative">
              <CustomSelectFilter
                v-model="form.per_page"
                :options="[
                  { label: '10', value: '10' },
                  { label: '25', value: '25' },
                  { label: '50', value: '50' },
                  { label: '100', value: '100' },
                ]"
                width="5.5rem"
                @update:modelValue="applyFilter"
              />
            </div>
            <span class="ml-2">entries</span>
          </div>

          <!-- Search -->
          <div class="relative flex-1 min-w-64">
            <input
              v-model="form.search"
              @input="debouncedApplyFilter"
              type="text"
              placeholder="Cari berdasarkan kolom yang ditampilkan..."
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

<script setup lang="ts">
import { ref } from "vue";
import CustomSelectFilter from "@/components/ui/CustomSelectFilter.vue";
import DateRangeFilter from "@/components/ui/DateRangeFilter.vue";

const props = defineProps<{ departmentOptions: any[]; supplierOptions: any[] }>();

const emit = defineEmits<{
  filter: [payload: any];
  reset: [];
}>();

const isFilterOpen = ref(false);
let debounceTimer: number | null = null;

const form = ref({
  tanggal_start: "",
  tanggal_end: "",
  no_bpb: "",
  no_po: "",
  no_pv: "",
  po_perihal: "",
  department_id: "",
  status: "",
  supplier_id: "",
  per_page: "10",
  search: "",
});

function toggleFilter() {
  isFilterOpen.value = !isFilterOpen.value;
  localStorage.setItem("bpbShowFilters", isFilterOpen.value ? "true" : "false");
}

function debouncedApplyFilter() {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  debounceTimer = setTimeout(() => {
    applyFilter();
  }, 300);
}

function applyFilter() {
  const payload: Record<string, any> = {};

  if (form.value.tanggal_start) payload.tanggal_start = form.value.tanggal_start;
  if (form.value.tanggal_end) payload.tanggal_end = form.value.tanggal_end;
  if (form.value.no_bpb) payload.no_bpb = form.value.no_bpb;
  if (form.value.no_po) payload.no_po = form.value.no_po;
  if (form.value.no_pv) payload.no_pv = form.value.no_pv;
  if (form.value.po_perihal) payload.po_perihal = form.value.po_perihal;
  if (form.value.department_id) payload.department_id = form.value.department_id;
  if (form.value.status) payload.status = form.value.status;
  if (form.value.supplier_id) payload.supplier_id = form.value.supplier_id;
  if (form.value.per_page) payload.per_page = form.value.per_page;

  // Only include search if it has actual content
  if (form.value.search && form.value.search.trim()) {
    payload.search = form.value.search.trim();
  }

  emit("filter", payload);
  window.dispatchEvent(new CustomEvent("content-changed"));
}

function resetFilter() {
  form.value = {
    tanggal_start: "",
    tanggal_end: "",
    no_bpb: "",
    no_po: "",
    no_pv: "",
    po_perihal: "",
    department_id: "",
    status: "",
    supplier_id: "",
    per_page: "10",
    search: "",
  };
  emit("reset");
  window.dispatchEvent(new CustomEvent("content-changed"));
}

// Initialize filter state from localStorage
const savedFilterState = localStorage.getItem("bpbShowFilters");
if (savedFilterState !== null) {
  isFilterOpen.value = savedFilterState === "true";
}
</script>

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
  max-height: 300px;
  margin-bottom: 0.75rem;
  padding-bottom: 1rem;
}

/* Custom style for inputs to match the design */
input[type="text"],
input[type="number"],
input[type="date"] {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: #fff;
  transition: box-shadow 0.2s, border-color 0.2s;
}

input[type="text"]:focus,
input[type="number"]:focus,
input[type="date"]:focus {
  border-color: #5856d6;
  box-shadow: 0 0 0 2px rgba(88, 86, 214, 0.2);
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
</style>
