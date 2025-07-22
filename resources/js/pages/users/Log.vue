<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import {
  User,
  Activity,
  Plus,
  Edit,
  Trash2,
  ArrowRight,
  ArrowLeft,
  FileText,
} from "lucide-vue-next";

defineOptions({ layout: AppLayout });

const props = defineProps({
  user: Object,
  logs: Object,
  filters: Object,
  roleOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  actionOptions: { type: Array, default: () => [] },
});

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "User", href: "/users" },
  { label: `${props.user?.name} - Log Activity` },
];

const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || "");
const actionFilter = ref(props.filters?.action || "");
const departmentFilter = ref(props.filters?.department || "");
const roleFilter = ref(props.filters?.role || "");
const dateFilter = ref(props.filters?.date || "");

watch(
  [entriesPerPage, actionFilter, departmentFilter, roleFilter, dateFilter],
  () => {
    applyFilters();
  },
  { immediate: false }
);

let searchTimeout: ReturnType<typeof setTimeout>;
watch(
  searchQuery,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      applyFilters();
    }, 500);
  },
  { immediate: false }
);

function applyFilters() {
  const params: Record<string, any> = {};

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (departmentFilter.value) params.department = departmentFilter.value;
  if (roleFilter.value) params.role = roleFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/users/${props.user?.id}/logs`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function handlePagination(url: string) {
  if (!url) return;

  const urlParams = new URLSearchParams(url.split("?")[1]);
  const page = urlParams.get("page");

  const params: Record<string, any> = { page };

  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (departmentFilter.value) params.department = departmentFilter.value;
  if (roleFilter.value) params.role = roleFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/users/${props.user?.id}/logs`, params, {
    preserveState: true,
    preserveScroll: true,
  });
}

function goBack() {
  router.visit("/users");
}

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

function getActivityIcon(action: string) {
  switch (action.toLowerCase()) {
    case "created":
      return Plus;
    case "updated":
      return Edit;
    case "deleted":
      return Trash2;
    case "out":
      return ArrowRight;
    case "received":
      return FileText;
    case "returned":
      return ArrowRight;
    default:
      return Activity;
  }
}

function getActivityColor(action: string, index: number) {
  return index === 0 ? "bg-blue-600" : "bg-gray-400";
}

function getDotClass(index: number) {
  if (index === 0) {
    return "w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow";
  }
  return "w-4 h-4 rounded-full border-2 border-gray-400 bg-white";
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
          <!-- <button
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
          </button> -->
        </div>
      </div>

      <!-- User Info Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 bg-[#7F9BE6] rounded-full flex items-center justify-center"
          >
            <User class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              {{ user?.name }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ user?.email }} • {{ user?.no_telepon }}
            </p>
          </div>
        </div>
      </div>

      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <!-- Activity Items -->
          <div
            v-for="(log, index) in logs && logs.data ? logs.data : []"
            :key="log.id"
            class="relative grid grid-cols-3 gap-6 py-4 hover:bg-gray-50 rounded-lg transition-colors duration-200"
          >
            <!-- Kolom 1: Activity Item -->
            <div class="flex items-center">
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
                      <span
                        v-if="log.user.role || log.user.department"
                        class="text-xs text-gray-400"
                      >
                        (
                        <template v-if="log.user.role">{{ log.user.role.name }}</template>
                        <template v-if="log.user.role && log.user.department">
                          •
                        </template>
                        <template v-if="log.user.department">{{
                          log.user.department.name
                        }}</template>
                        )
                      </span>
                    </span>
                    <span v-else> by System </span>
                  </template>
                </p>
              </div>
            </div>

            <!-- Kolom 2: Activity Icon + Timeline -->
            <div class="flex items-center justify-start gap-12 relative">
              <!-- Activity Icon -->
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg',
                  getActivityColor(log.action, index),
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
                  v-if="logs && logs.data && index !== logs.data.length - 1"
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
            v-if="!logs?.data || logs.data.length === 0"
            class="text-center py-12 col-span-3"
          >
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">
              There are no activities recorded for this user.
            </p>
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
            <template v-for="(link, index) in logs?.links?.slice(1, -1)" :key="index">
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
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <ArrowLeft class="w-4 h-4" />
          Back to Users
        </button>
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
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Timeline Dot Outline */
/* .w-4.h-4.rounded-full.border-2 {
  background: #fff !important;
} */

.bg-blue-600 {
  background-color: #2563eb !important;
}

.dot-glow {
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2),
    0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
