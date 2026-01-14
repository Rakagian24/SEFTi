<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <LogScaffold
        :breadcrumbs="breadcrumbs"
        headerTitle="Realisasi Activity Details"
        infoTitle="Realisasi Activities"
        :infoSubtitle="`Riwayat aktivitas untuk Realisasi #${ props.realisasi?.id ?? '' }`"
      >
        <!-- Activity Timeline Section -->
        <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
          <div class="space-y-0">
            <!-- Activity Items -->
            <div
              v-for="(log, index) in logsList"
              :key="log.id"
              class="relative grid grid-cols-3 gap-6 py-4 hover:bg-gray-50 rounded-lg transition-colors duration-200"
            >
              <!-- Kolom 1: Activity Item -->
              <div class="flex items-center">
                <div class="text-left">
                  <h3 class="text-lg font-semibold text-gray-900 capitalize mb-1">
                    {{ getActionDescription(log.action) }}
                  </h3>
                  <p class="text-sm text-gray-600">
                    <template v-if="log.user">
                      Oleh {{ log.user.name }} -
                      {{ displayUserRole(log.user) }}
                    </template>
                    <template v-else>Oleh System</template>
                  </p>
                </div>
              </div>

              <!-- Kolom 2: Activity Icon + Timeline -->
              <div class="flex items-center justify-start gap-12 relative">
                <!-- Activity Icon -->
                <div
                  :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg',
                    getActivityColor(log.action),
                    index === 0 ? 'dot-glow' : '',
                  ]"
                >
                  <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
                </div>

                <!-- Timeline Section -->
                <div class="flex flex-col items-center relative">
                  <!-- Timeline Dot -->
                  <div :class="getDotClass(index)"></div>

                  <!-- Timeline Line -->
                  <div
                    v-if="index !== logsList.length - 1"
                    class="w-0.5 h-16 bg-gray-200 absolute top-4"
                  ></div>
                </div>
              </div>

              <!-- Kolom 3: Timestamp -->
              <div class="flex items-center justify-end">
                <div class="text-right">
                  <div class="text-sm text-gray-500">
                    {{ formatDateTime(log.created_at) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div
              v-if="!logsList || logsList.length === 0"
              class="text-center py-12 col-span-3"
            >
              <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
              <p class="text-gray-500">
                There are no activities recorded for this realisasi.
              </p>
            </div>
          </div>

          <!-- Pagination -->
          <div
            v-if="logsList && logsList.length > 0 && pagination.links"
            class="mt-8 flex items-center justify-center border-t border-gray-200 pt-6"
          >
            <nav class="flex items-center space-x-2" aria-label="Pagination">
              <!-- Previous Button -->
              <button
                @click="prevPage"
                :disabled="!pagination.prev_page_url"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                  pagination.prev_page_url
                    ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
                    : 'text-gray-400 cursor-not-allowed',
                ]"
              >
                Previous
              </button>

              <!-- Page Numbers -->
              <template
                v-for="(link, index) in pagination?.links?.slice(1, -1)"
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
                @click="nextPage"
                :disabled="!pagination.next_page_url"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                  pagination.next_page_url
                    ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
                    : 'text-gray-400 cursor-not-allowed',
                ]"
              >
                Next
              </button>
            </nav>
          </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
          <button
            @click="goBack"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            Kembali ke Realisasi
          </button>
        </div>
      </LogScaffold>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Activity } from "lucide-vue-next";
import { getActionDescription as baseGetDesc, getActivityIcon as baseGetIcon, getActivityColor as baseGetColor } from "@/lib/activity";
import LogScaffold from "@/components/logs/LogScaffold.vue";
import { transformRoleLabel } from '@/lib/roleUtils';

defineOptions({ layout: AppLayout });
const props = defineProps({
  realisasi: Object,
  logs: { type: [Object, Array], default: () => [] },
  filters: Object,
  roleOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  actionOptions: { type: Array, default: () => [] },
});

const realisasiId = (props.realisasi as any)?.id;
const docDeptName = (props.realisasi as any)?.department?.name ?? '';
const logsList = computed<any[]>(() => {
  const value = props.logs as any;
  return Array.isArray(value) ? value : value?.data ?? [];
});
const pagination = computed(() => {
  const value = props.logs as any;
  if (Array.isArray(value)) {
    return { links: null, prev_page_url: null, next_page_url: null } as const;
  }
  return {
    links: value?.links ?? null,
    prev_page_url: value?.prev_page_url ?? null,
    next_page_url: value?.next_page_url ?? null,
  } as const;
});

const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || "");
const actionFilter = ref(props.filters?.action || "");
const departmentFilter = ref(props.filters?.department || "");
const roleFilter = ref(props.filters?.role || "");
const dateFilter = ref(props.filters?.date || "");

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Realisasi", href: "/realisasi" },
  { label: "Log Aktivitas" },
];

const getActionDescription = (action: string) => baseGetDesc(action, "Realisasi");

function formatDateTime(dateString: string) {
  const date = new Date(dateString);
  const tanggal = date.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const jam = date.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });
  return `${tanggal} - ${jam}`;
}

const getActivityIcon = (action: string) => baseGetIcon(action);

const getActivityColor = (action: string) => baseGetColor(action);

function getDotClass(index: number) {
  if (index === 0) {
    return "w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow";
  }
  return "w-4 h-4 rounded-full border-2 border-gray-400 bg-white";
}

function displayUserRole(user: any): string {
  if (!user) return '';
  const roleName = user.role?.name || '';
  return transformRoleLabel(roleName, docDeptName);
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/realisasi");
  }
}

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (departmentFilter.value) params.department = departmentFilter.value;
  if (roleFilter.value) params.role = roleFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/realisasi/${realisasiId}/log`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function handlePagination(url: string | null) {
  if (!url) return;

  const urlParams = new URLSearchParams(url.split("?")[1]);
  const page = urlParams.get("page");

  const params: Record<string, any> = { page };

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (roleFilter.value) params.role = roleFilter.value;
  if (departmentFilter.value) params.department = departmentFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/realisasi/${realisasiId}/log`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function prevPage() {
  handlePagination(pagination.value.prev_page_url ?? null);
}

function nextPage() {
  handlePagination(pagination.value.next_page_url ?? null);
}

watch(
  [entriesPerPage, actionFilter, departmentFilter, roleFilter, dateFilter],
  () => {
    applyFilters();
  },
  { immediate: false }
);

let searchTimeout: ReturnType<typeof setTimeout>;
watch(
  () => searchQuery.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      applyFilters();
    }, 500);
  },
  { immediate: false }
);
</script>

<style scoped>
/* Timeline enhancements */
.timeline-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

/* Responsive design */
@media (max-width: 768px) {
  .grid-cols-3 {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .text-right {
    text-align: left !important;
  }

  .justify-end {
    justify-content: flex-start !important;
  }
}

/* Activity icon animations */
.w-10.h-10 {
  transition: all 0.3s ease;
}

.w-10.h-10:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Filter animation */
.rotate-45 {
  transform: rotate(45deg);
}

.rotate-0 {
  transform: rotate(0deg);
}

/* Pagination enhancements */
nav button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

nav button:disabled {
  opacity: 0.5;
}

nav button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Timeline line styling */
.bg-gray-200 {
  background: linear-gradient(to bottom, #e5e7eb, #f3f4f6);
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.bg-blue-600 {
  background-color: #2563eb !important;
}

.dot-glow {
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2),
    0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
