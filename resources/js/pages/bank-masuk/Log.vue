<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import {
  Activity,
  Plus,
  Edit,
  Trash2,
  ArrowRight,
  ArrowLeft,
  CreditCard
} from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
  bankMasuk: Object,
});
const page = usePage();
const logs = (page.props.bankMasukLog || []) as any[];

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Masuk", href: "/bank-masuk" },
  { label: props.bankMasuk?.no_bm ? `${props.bankMasuk.no_bm} - Log` : "Log" }
];

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.get('/bank-masuk');
  }
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
    case "create":
      return Plus;
    case "updated":
    case "update":
      return Edit;
    case "deleted":
    case "delete":
      return Trash2;
    case "out":
      return ArrowRight;
    case "received":
      return CreditCard;
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
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Log Aktivitas Bank Masuk</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Activity class="w-4 h-4 mr-1" />
            These are the activities that have been recorded for bank masuk.
          </div>
        </div>
      </div>

      <!-- Bank Masuk Info Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 bg-[#7F9BE6] rounded-full flex items-center justify-center"
          >
            <CreditCard class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              Bank Masuk Activities
            </h3>
            <p class="text-sm text-gray-500">
              Riwayat aktivitas untuk modul Bank Masuk
            </p>
          </div>
        </div>
      </div>

      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <!-- Activity Items -->
          <div
            v-for="(log, index) in logs"
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
                  v-if="index !== logs.length - 1"
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
            v-if="!logs || logs.length === 0"
            class="text-center py-12 col-span-3"
          >
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">
              Belum ada aktivitas yang tercatat untuk Bank Masuk.
            </p>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <ArrowLeft class="w-4 h-4" />
          Kembali ke Bank Masuk
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
