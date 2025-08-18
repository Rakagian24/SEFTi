<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Log Aktivitas Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Activity class="w-4 h-4 mr-1" />
            These are the activities that have been recorded for purchase order.
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
            <h3 class="text-lg font-semibold text-gray-900">Purchase Order Activities</h3>
            <p class="text-sm text-gray-500">Riwayat aktivitas untuk Purchase Order</p>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-t-lg shadow-sm border border-gray-200 p-6 border-b-0">
        <div class="flex flex-wrap gap-3">
          <input
            v-model="filters.search"
            placeholder="Cari user/deskripsi..."
            class="input input-bordered flex-1 min-w-[200px]"
          />
          <select v-model="filters.action" class="select select-bordered min-w-[140px]">
            <option value="">Semua Aksi</option>
            <option v-for="action in actionOptions" :key="action" :value="action">
              {{ action }}
            </option>
          </select>
          <select v-model="filters.role" class="select select-bordered min-w-[140px]">
            <option value="">Semua Role</option>
            <option v-for="role in roleOptions" :key="role.id" :value="role.name">
              {{ role.name }}
            </option>
          </select>
          <select
            v-model="filters.department"
            class="select select-bordered min-w-[160px]"
          >
            <option value="">Semua Departemen</option>
            <option v-for="dept in departmentOptions" :key="dept.id" :value="dept.name">
              {{ dept.name }}
            </option>
          </select>
          <input
            v-model="filters.date"
            type="date"
            class="input input-bordered min-w-[140px]"
          />
          <button @click="() => fetchLogs()" class="btn btn-primary px-6">Filter</button>
        </div>
      </div>

      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <!-- Activity Items -->
          <div
            v-for="(log, index) in logs.data"
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
                  <span v-if="log.user">
                    by {{ log.user.name }}
                    <span
                      v-if="log.user.role || log.user.department"
                      class="text-xs text-gray-400"
                    >
                      (
                      <template v-if="log.user.role">{{ log.user.role.name }}</template>
                      <template v-if="log.user.role && log.user.department"> • </template>
                      <template v-if="log.user.department">{{
                        log.user.department.name
                      }}</template>
                      )
                    </span>
                  </span>
                  <span v-else> by System </span>
                </p>
                <p v-if="log.description" class="text-xs text-gray-500 mt-1">
                  {{ log.description }}
                </p>
                <p v-if="log.ip_address" class="text-xs text-gray-400 mt-1">
                  IP: {{ log.ip_address }}
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
                  v-if="index !== logs.data.length - 1"
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
            v-if="!logs.data || logs.data.length === 0"
            class="text-center py-12 col-span-3"
          >
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">
              Belum ada aktivitas yang tercatat untuk Purchase Order ini.
            </p>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex justify-center">
        <div class="flex items-center gap-2">
          <button
            @click="prevPage"
            :disabled="!logs.prev_page_url"
            class="btn btn-sm bg-white hover:bg-gray-50 text-gray-600 border-gray-300 disabled:opacity-50"
          >
            <ArrowLeft class="w-4 h-4 mr-1" />
            Previous
          </button>
          <button
            @click="nextPage"
            :disabled="!logs.next_page_url"
            class="btn btn-sm bg-white hover:bg-gray-50 text-gray-600 border-gray-300 disabled:opacity-50"
          >
            Next
            <ArrowRight class="w-4 h-4 ml-1" />
          </button>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <ArrowLeft class="w-4 h-4" />
          Kembali ke Purchase Order
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import {
  Activity,
  Plus,
  Edit,
  Trash2,
  ArrowRight,
  ArrowLeft,
  FileText,
} from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";

interface Role {
  id: number;
  name: string;
}
interface Department {
  id: number;
  name: string;
}
interface User {
  name: string;
  role?: Role;
  department?: Department;
}
interface Log {
  id: number;
  created_at: string;
  user?: User;
  action: string;
  description: string;
  ip_address: string;
}
interface LogsResponse {
  data: Log[];
  prev_page_url?: string;
  next_page_url?: string;
}

const purchaseOrderId = route.params.id;
const logs = ref<LogsResponse>({ data: [] });
const filters = ref({
  search: "",
  action: "",
  role: "",
  department: "",
  date: "",
  per_page: 10,
});
const actionOptions = ref<string[]>([]);
const roleOptions = ref<Role[]>([]);
const departmentOptions = ref<Department[]>([]);

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Log Aktivitas" },
];

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
    case "create":
      return Plus;
    case "updated":
    case "update":
      return Edit;
    case "deleted":
    case "delete":
      return Trash2;
    case "approved":
    case "approve":
      return ArrowRight;
    case "rejected":
    case "reject":
      return ArrowLeft;
    case "submitted":
    case "submit":
      return FileText;
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

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/purchase-orders");
  }
}

async function fetchLogs(pageUrl: string | null = null) {
  try {
    const url = pageUrl || `/purchase-orders/${purchaseOrderId}/log`;
    const { search, action, role, department, date, per_page } = filters.value;
    const params = { search, action, role, department, date, per_page };
    const { data } = await axios.get(url, { params });
    logs.value = data.logs;

    // Set options if backend provides them
    if (data.roleOptions) roleOptions.value = data.roleOptions;
    if (data.departmentOptions) departmentOptions.value = data.departmentOptions;
    if (data.actionOptions) actionOptions.value = data.actionOptions;
  } catch (error) {
    console.error("Error fetching logs:", error);
  }
}

function prevPage() {
  if (logs.value.prev_page_url) fetchLogs(logs.value.prev_page_url);
}

function nextPage() {
  if (logs.value.next_page_url) fetchLogs(logs.value.next_page_url);
}

onMounted(fetchLogs);
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

.bg-blue-600 {
  background-color: #2563eb !important;
}

.dot-glow {
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2),
    0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
