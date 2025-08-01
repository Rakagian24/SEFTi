<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['filter-changed']);

const startDate = ref(props.filters?.start_date || new Date().toISOString().slice(0, 10));
const endDate = ref(props.filters?.end_date || new Date().toISOString().slice(0, 10));
const searchQuery = ref(props.filters?.search || '');
const entriesPerPage = ref(props.filters?.per_page || 10);

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

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (startDate.value) params.start_date = startDate.value;
  if (endDate.value) params.end_date = endDate.value;

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

function handleDateChange() {
  applyFilters();
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
            <span class="text-sm text-gray-600">Dari:</span>
            <Datepicker
              v-model="startDate"
              @update:model-value="handleDateChange"
              format="yyyy-MM-dd"
              :enable-time-picker="false"
              :auto-apply="true"
              :close-on-auto-apply="true"
              class="w-32"
            />
          </div>

          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Sampai:</span>
            <Datepicker
              v-model="endDate"
              @update:model-value="handleDateChange"
              format="yyyy-MM-dd"
              :enable-time-picker="false"
              :auto-apply="true"
              :close-on-auto-apply="true"
              class="w-32"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
