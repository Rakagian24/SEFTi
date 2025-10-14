<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
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
      </div>

      <!-- Purchase Order Info Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 bg-[#7F9BE6] rounded-full flex items-center justify-center"
          >
            <FileText class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              {{ purchaseOrder?.nomor_po || purchaseOrder?.no_po }} Activities
            </h3>
            <p class="text-sm text-gray-500">Riwayat aktivitas untuk Purchase Order</p>
          </div>
        </div>
      </div>

      <!-- Reuse the same timeline section UI as PO Log -->
      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <div
            v-for="(log, index) in logsList"
            :key="log.id"
            class="relative grid grid-cols-3 gap-6 py-4 hover:bg-gray-50 rounded-lg transition-colors duration-200"
          >
            <div class="flex items-center">
              <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-900 capitalize mb-1">
                  {{ getActionDescription(log.action) }}
                </h3>
                <p class="text-sm text-gray-600">
                  <template v-if="log.user">
                    Oleh {{ log.user.name }} -
                    {{ log.user.role ? log.user.role.name : "" }}
                  </template>
                  <template v-else>Oleh System</template>
                </p>
              </div>
            </div>

            <div class="flex items-center justify-start gap-12 relative">
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg',
                  getActivityColor(log.action, index),
                  index === 0 ? 'dot-glow' : '',
                ]"
              >
                <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
              </div>

              <div class="flex flex-col items-center relative">
                <div :class="getDotClass(index)"></div>
                <div
                  v-if="index !== logsList.length - 1"
                  class="w-0.5 h-16 bg-gray-200 absolute top-4"
                ></div>
              </div>
            </div>

            <div class="flex items-center justify-end">
              <div class="text-right">
                <div class="text-sm text-gray-500">
                  {{ formatDateTime(log.created_at) }}
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="!logsList || logsList.length === 0"
            class="text-center py-12 col-span-3"
          >
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">
              There are no activities recorded for this purchase order.
            </p>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="logsList && logsList.length > 0"
          class="mt-8 flex items-center justify-center border-t border-gray-200 pt-6"
        >
          <nav class="flex items-center space-x-2" aria-label="Pagination">
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
          Kembali ke Approval Purchase Order
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Activity } from "lucide-vue-next";
import { getActionDescription as baseGetDesc, getActivityIcon as baseGetIcon, getActivityColor as baseGetColor } from "@/lib/activity";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
  purchaseOrder: Object,
  logs: { type: [Object, Array], default: () => [] },
  filters: Object,
  roleOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  actionOptions: { type: Array, default: () => [] },
});

const purchaseOrderId = (props.purchaseOrder as any)?.id;
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

const entriesPerPage = ref((props.filters as any)?.per_page || 10);
const searchQuery = ref((props.filters as any)?.search || "");
const actionFilter = ref((props.filters as any)?.action || "");
const departmentFilter = ref((props.filters as any)?.department || "");
const roleFilter = ref((props.filters as any)?.role || "");
const dateFilter = ref((props.filters as any)?.date || "");

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Purchase Order", href: "/approval/purchase-orders" },
  { label: "Log Aktivitas" },
];

const getActionDescription = (action: string) => baseGetDesc(action, "Purchase Order");

function formatDateTime(dateString: string) {
  const date = new Date(dateString);
  const tanggal = date.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const jam = date.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
  return `${tanggal} - ${jam}`;
}

const getActivityIcon = (action: string) => baseGetIcon(action);

const getActivityColor = (action: string, index: number) => baseGetColor(action, index);

function getDotClass(index: number) {
  if (index === 0)
    return "w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow";
  return "w-4 h-4 rounded-full border-2 border-gray-400 bg-white";
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/approval/purchase-orders");
  }
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
  router.get(`/approval/purchase-orders/${purchaseOrderId}/log`, params, {
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
</script>

<style scoped>
.timeline-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}
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
.w-10.h-10 {
  transition: all 0.3s ease;
}
.w-10.h-10:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.rotate-45 {
  transform: rotate(45deg);
}
.rotate-0 {
  transform: rotate(0deg);
}
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
.bg-gray-200 {
  background: linear-gradient(to bottom, #e5e7eb, #f3f4f6);
}
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
