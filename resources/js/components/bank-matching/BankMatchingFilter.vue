<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import CustomSelectFilter from "../ui/CustomSelectFilter.vue";

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  },
  departments: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['filter-changed']);

const dateRange = ref<(Date|null)[]>([
  props.filters?.start_date ? new Date(props.filters.start_date) : null,
  props.filters?.end_date ? new Date(props.filters.end_date) : null
]);
const searchQuery = ref(props.filters?.search || '');
const entriesPerPage = ref(props.filters?.per_page || 10);
const selectedDepartment = ref(props.filters?.department_id || '');
const departments = ref(props.departments || []);

// Computed property for datepicker range
const datepickerRange = computed<Date[]|undefined>(() => {
  if (dateRange.value[0] && dateRange.value[1]) return [dateRange.value[0] as Date, dateRange.value[1] as Date];
  if (dateRange.value[0]) return [dateRange.value[0] as Date];
  if (dateRange.value[1]) return [dateRange.value[1] as Date];
  return undefined;
});

// Format function for date range display
function formatRange(dates: Date[]) {
  if (!dates || !dates[0] || !dates[1]) return ''
  const options: Intl.DateTimeFormatOptions = {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }
  const start = dates[0].toLocaleDateString('id-ID', options)
  const end = dates[1].toLocaleDateString('id-ID', options)
  return `${start} to ${end}`
}

// Debounce timer untuk search
let searchTimeout: ReturnType<typeof setTimeout>;

// Watch search dengan debounce
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500); // 500ms debounce
}, { immediate: false });

// Watch untuk entries per page
watch(entriesPerPage, () => {
  applyFilters();
}, { immediate: false });

// Watch untuk department selection
watch(selectedDepartment, () => {
  applyFilters();
}, { immediate: false });

// Watch untuk date range changes
watch(dateRange, () => {
  applyFilters();
}, { immediate: false });

// Load departments on mount
onMounted(() => {
  // loadDepartments(); // Removed as departments are now passed as props
});

// async function loadDepartments() { // Removed as departments are now passed as props
//   loadingDepartments.value = true;
//   try {
//     const response = await fetch('/api/departments');
//     const data = await response.json();
//     departments.value = data.data || [];
//   } catch (error) {
//     console.error('Error loading departments:', error);
//     departments.value = [];
//   } finally {
//     loadingDepartments.value = false;
//   }
// }

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (dateRange.value[0]) params.start_date = dateRange.value[0]?.toISOString().slice(0, 10);
  if (dateRange.value[1]) params.end_date = dateRange.value[1]?.toISOString().slice(0, 10);
  if (selectedDepartment.value) params.department_id = selectedDepartment.value;
  // Preserve current tab from URL
  const urlParams = new URLSearchParams(window.location.search);
  const currentTab = urlParams.get('tab');
  if (currentTab) {
    params.tab = currentTab;
  }

  // Emit event untuk memberitahu parent component
  emit('filter-changed', params);

  router.get('/bank-matching', params, {
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function updateSelectedDepartment(value: string) {
  selectedDepartment.value = value;
  applyFilters();
}

function onDateRangeChange(val: (Date|null)[]) {
  dateRange.value = val;
}
</script>

<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter Controls -->
        <div class="flex items-center gap-4 flex-wrap">
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-gray-500" />
            <span class="text-sm font-medium text-gray-700">Periode:</span>
          </div>

          <div class="flex items-center gap-2">
            <Datepicker
              v-model="datepickerRange"
              @update:model-value="onDateRangeChange"
              range
              :format="formatRange"
              :enable-time-picker="false"
              :auto-apply="true"
              :close-on-auto-apply="true"
              placeholder="Pilih rentang tanggal"
              class="w-64"
            />
          </div>

          <div v-if="(Array.isArray(departments) ? departments.length : 0) !== 1" class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Departemen:</span>
            <div class="flex items-center gap-2">
              <CustomSelectFilter
                :model-value="selectedDepartment"
                @update:modelValue="updateSelectedDepartment"
                :options="[
                  { label: 'Semua Departemen', value: '' },
                  ...departments.map((department: any) => ({
                    label: department.name,
                    value: String(department.id)
                  }))
                ]"
                placeholder="Pilih Departemen"
                width="12rem"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
