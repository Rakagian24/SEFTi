<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import CustomSelectFilter from "@/components/ui/CustomSelectFilter.vue";
import { Calendar, User, Activity } from "lucide-vue-next";

defineOptions({ layout: AppLayout });

const props = defineProps({
  bisnisPartner: Object,
  logs: Object,
  filters: Object,
});

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bisnis Partner", href: "/bisnis-partners" },
  { label: `${props.bisnisPartner?.nama_bp} - Log Activity` }
];

// Initialize reactive filters from props
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const actionFilter = ref(props.filters?.action || '');
const dateFilter = ref(props.filters?.date || '');

// Watch for changes and apply filters automatically
watch([entriesPerPage, actionFilter, dateFilter], () => {
  applyFilters();
}, { immediate: false });

// Watch search query with debouncing
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500); // 500ms debounce
}, { immediate: false });

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/bisnis-partners/${props.bisnisPartner?.id}/logs`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function resetFilters() {
  searchQuery.value = '';
  actionFilter.value = '';
  dateFilter.value = '';
  entriesPerPage.value = 10;

  router.get(`/bisnis-partners/${props.bisnisPartner?.id}/logs`, { per_page: 10 }, {
    preserveState: true,
  });
}

function handlePagination(url: string) {
  if (!url) return;

  // Extract page number from URL
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');

  const params: Record<string, any> = { page };

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/bisnis-partners/${props.bisnisPartner?.id}/logs`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function goBack() {
  router.visit('/bisnis-partners');
}

function formatDate(dateString: string) {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getActionBadgeClass(action: string) {
  switch (action.toLowerCase()) {
    case 'created':
      return 'bg-green-100 text-green-800';
    case 'updated':
      return 'bg-blue-100 text-blue-800';
    case 'deleted':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

const showFilters = ref(false);
function toggleFilters() {
  showFilters.value = !showFilters.value;
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Log Activity</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Activity class="w-4 h-4 mr-1" />
            Log activity untuk {{ bisnisPartner?.nama_bp }}
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Back Button -->
          <button
            @click="goBack"
            class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            Back
          </button>
        </div>
      </div>

      <!-- Business Partner Info Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-[#7F9BE6] rounded-full flex items-center justify-center">
            <User class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ bisnisPartner?.nama_bp }}</h3>
            <p class="text-sm text-gray-500">{{ bisnisPartner?.jenis_bp }} • {{ bisnisPartner?.email }}</p>
          </div>
        </div>
      </div>

      <!-- Filter Section -->
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
              <div v-if="showFilters" class="mt-3 flex flex-wrap items-center gap-x-0 gap-y-2 max-w-full">
                <!-- Action Filter -->
                <div class="flex-shrink-0">
                  <CustomSelectFilter
                    :model-value="actionFilter ?? ''"
                    @update:modelValue="(val) => actionFilter = val"
                    :options="[
                      { label: 'All Actions', value: '' },
                      { label: 'Created', value: 'created' },
                      { label: 'Updated', value: 'updated' },
                      { label: 'Deleted', value: 'deleted' },
                    ]"
                    placeholder="Action"
                    style="min-width: calc(10ch + 2rem); padding-left: 0.75rem; padding-right: 0.75rem;"
                  />
                </div>

                <!-- Date Filter -->
                <div class="flex-shrink-0">
                  <input
                    v-model="dateFilter"
                    type="date"
                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
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
                    :model-value="entriesPerPage"
                    @update:modelValue="(val) => entriesPerPage = val"
                    :options="[
                      { label: '10', value: 10 },
                      { label: '25', value: 25 },
                      { label: '50', value: 50 },
                      { label: '100', value: 100 }
                    ]"
                    style="min-width: 5.5rem;"
                  />
                </div>
                <span class="ml-2">entries</span>
              </div>

              <!-- Search -->
              <div class="relative flex-1 min-w-64">
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

      <!-- Table Section -->
      <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
        <div class="overflow-x-auto rounded-lg">
          <table class="min-w-full">
            <thead class="bg-[#FFFFFF] border-b border-gray-200">
              <tr>
                <th
                  class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                >
                  Tanggal & Waktu
                </th>
                <th
                  class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                >
                  Action
                </th>
                <th
                  class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                >
                  User
                </th>
                <th
                  class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                >
                  Description
                </th>
                <th
                  class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                >
                  IP Address
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="log in logs?.data" :key="log.id" class="alternating-row">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center">
                    <Calendar class="w-4 h-4 mr-2 text-gray-400" />
                    {{ formatDate(log.created_at) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      getActionBadgeClass(log.action)
                    ]"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center">
                    <User class="w-4 h-4 mr-2 text-gray-400" />
                    {{ log.user?.name || 'System' }}
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-md">
                  <div class="truncate" :title="log.description">
                    {{ log.description }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ log.ip_address || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
        >
          <nav class="flex items-center space-x-2" aria-label="Pagination">
            <!-- Previous Button -->
            <button
              @click="handlePagination(logs?.prev_page_url)"
              :disabled="!logs?.prev_page_url"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                logs?.prev_page_url
                  ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
                  : 'text-gray-400 cursor-not-allowed',
              ]"
            >
              Previous
            </button>

            <!-- Page Numbers -->
            <template
              v-for="(link, index) in logs?.links?.slice(1, -1)"
              :key="index"
            >
              <button
                @click="handlePagination(link.url)"
                :disabled="!link.url"
                :class="[
                  'w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200',
                  link.active
                    ? 'bg-black text-white'
                    : link.url
                    ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                    : 'bg-gray-200 text-gray-400 cursor-not-allowed',
                ]"
                v-html="link.label"
              ></button>
            </template>

            <!-- Next Button -->
            <button
              @click="handlePagination(logs?.next_page_url)"
              :disabled="!logs?.next_page_url"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                logs?.next_page_url
                  ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
                  : 'text-gray-400 cursor-not-allowed',
              ]"
            >
              Next
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Alternating row colors */
.alternating-row:nth-child(even) {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

/* Hover effect for alternating rows */
.alternating-row:nth-child(even):hover {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover {
  background-color: #f8fafc;
}

/* Animasi untuk ikon plus */
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
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

/* Pagination styling enhancements */
nav button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Disabled button styling */
nav button:disabled {
  opacity: 0.5;
}

/* Hover effects for pagination buttons */
nav button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
