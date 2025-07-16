<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { User, Activity, Plus, Edit, Trash2, ArrowRight, FileText } from "lucide-vue-next";

defineOptions({ layout: AppLayout });

const props = defineProps({
  bisnisPartner: Object,
  logs: Object,
  filters: Object,
  roleOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  actionOptions: { type: Array, default: () => [] },
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
const departmentFilter = ref(props.filters?.department || '');
const roleFilter = ref(props.filters?.role || '');
const dateFilter = ref(props.filters?.date || '');

// Watch for changes and apply filters automatically
watch([entriesPerPage, actionFilter, departmentFilter, roleFilter, dateFilter], () => {
  applyFilters();
}, { immediate: false });

// Watch search query with debouncing
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
}, { immediate: false });

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (departmentFilter.value) params.department = departmentFilter.value;
  if (roleFilter.value) params.role = roleFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/bisnis-partners/${props.bisnisPartner?.id}/logs`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function handlePagination(url: string) {
  if (!url) return;

  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');

  const params: Record<string, any> = { page };

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (departmentFilter.value) params.department = departmentFilter.value;
  if (roleFilter.value) params.role = roleFilter.value;
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

function formatDateTime(dateString: string) {
  const date = new Date(dateString);
  const tanggal = date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  const jam = date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  });
  return `${tanggal} - ${jam}`;
}

function getActivityIcon(action: string) {
  switch (action.toLowerCase()) {
    case 'created':
      return Plus;
    case 'updated':
      return Edit;
    case 'deleted':
      return Trash2;
    case 'out':
      return ArrowRight;
    case 'received':
      return FileText;
    case 'returned':
      return ArrowRight;
    default:
      return Activity;
  }
}

function getActivityColor(action: string, index: number) {
  // Blue for the latest (index 0), gray for others
  return index === 0 ? 'bg-blue-600' : 'bg-gray-400';
}

function getDotClass(index: number) {
  if (index === 0) {
    // Dot biru penuh + glow
    return 'w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow';
  }
  // Outline abu-abu untuk yang lain
  return 'w-4 h-4 rounded-full border-2 border-gray-400 bg-white';
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
          <h1 class="text-2xl font-bold text-gray-900">Displays Activity Details</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Activity class="w-4 h-4 mr-1" />
            These are the activities that have been recorded.
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

      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-6">
          <!-- Activity Item -->
          <div
            v-for="(log, index) in logs && logs.data ? logs.data : []"
            :key="log.id"
            class="relative flex items-center py-4"
          >
            <!-- Info + Icon (sejajar) -->
            <div class="flex items-center flex-1 min-w-0">
              <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-900 capitalize mb-1">
                  {{ log.action }}
                </h3>
                <p class="text-sm text-gray-600">
                  <template v-if="log.forwarded_by">
                    {{ `Forwarded by ${log.forwarded_by}` }}
                  </template>
                  <template v-else-if="log.accepted_by">
                    {{ `Accepted by ${log.accepted_by}` }}
                  </template>
                  <template v-else>
                    <span v-if="log.user">
                      by {{ log.user.name }}
                      <span v-if="log.user.role || log.user.department" class="text-xs text-gray-400">
                        (
                        <template v-if="log.user.role">{{ log.user.role.name }}</template>
                        <template v-if="log.user.role && log.user.department"> • </template>
                        <template v-if="log.user.department">{{ log.user.department.name }}</template>
                        )
                      </span>
                    </span>
                    <span v-else>
                      by System
                    </span>
                  </template>
                </p>
                <!-- Timestamp on mobile -->
                <div class="block md:hidden mt-2">
                  <div class="text-sm text-gray-500">
                    {{ formatDateTime(log.created_at) }}
                  </div>
                </div>
              </div>
              <!-- Activity Icon -->
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg ml-12',
                  getActivityColor(log.action, index),
                  index === 0 ? 'dot-glow' : ''
                ]"
              >
                <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
              </div>
              <!-- Timeline Section -->
            <div class="flex flex-col items-center relative ml-12">
              <div
                :class="getDotClass(index)"
              ></div>
              <div
                v-if="logs && logs.data && index !== logs.data.length - 1"
                class="w-0.5 h-25 bg-gray-200 absolute top-4"
              ></div>
            </div>
            </div>

            <!-- Spacer to push timestamp to the right -->
            <div class="hidden md:block flex-1 ml-4"></div>

            <!-- Timestamp Section (Desktop only) -->
            <div class="hidden md:block text-right min-w-[120px] ml-4">
              <div class="text-sm text-gray-500">
                {{ formatDateTime(log.created_at) }}
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!logs?.data || logs.data.length === 0" class="text-center py-12">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">There are no activities recorded for this business partner.</p>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="logs?.data && logs.data.length > 0"
          class="mt-8 flex items-center justify-center border-t border-gray-200 pt-6"
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
/* Timeline enhancements */
.timeline-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

/* Responsive design */
@media (max-width: 768px) {
  .flex-1 {
    flex: none;
    width: 100%;
  }
  .mx-8 {
    margin-left: 1rem;
    margin-right: 1rem;
  }
  .pr-6, .pl-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  .text-right {
    text-align: left !important;
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
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Timeline Dot Outline */
.absolute.left-10.top-4.w-4.h-4.rounded-full.border-2 {
  background: #fff !important;
}

.bg-blue-600 {
  background-color: #2563eb !important;
}

.dot-glow {
  box-shadow:
    0 0 0 0px rgba(37, 99, 235, 0.0),
    0 0 16px 8px rgba(37, 99, 235, 0.20), /* glow tipis dekat, lebih tebal dan terang */
    0 0 24px 12px rgba(37, 99, 235, 0.12),
    0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
