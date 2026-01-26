<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 md:px-6 pt-4 md:pt-6 pb-6">
      <LogScaffold
        :breadcrumbs="breadcrumbs"
        headerTitle="Payment Voucher Activity Details"
        infoTitle="Payment Voucher Activities"
        :infoSubtitle="`Riwayat aktivitas untuk Payment Voucher #${no_pv}`"
      >
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
                  {{ getActionDescription(log.action) }}
                </h3>
                <p class="text-sm text-gray-600">Oleh {{ log.user }} - {{ log.role || '' }}</p>
              </div>
            </div>

            <!-- Kolom 2: Activity Icon + Timeline (hidden on mobile for simpler view) -->
            <div class="hidden md:flex items-center justify-start gap-12 relative">
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
                  v-if="index !== logs.length - 1"
                  class="w-0.5 h-16 bg-gray-200 absolute top-4"
                ></div>
              </div>
            </div>

            <!-- Kolom 3: Timestamp -->
            <div class="flex items-center justify-end">
              <div class="text-right">
                <div class="text-sm text-gray-500">
                  {{ formatDateTime(log.at) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!logs || logs.length === 0" class="text-center py-12 col-span-3">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">Belum ada aktivitas untuk payment voucher ini.</p>
          </div>
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
          Kembali ke Payment Voucher
        </button>
      </div>
      </LogScaffold>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";
import { Activity } from "lucide-vue-next";
import {
  getActionDescription as baseGetDesc,
  getActivityIcon as baseGetIcon,
  getActivityColor as baseGetColor,
} from "@/lib/activity";
import LogScaffold from "@/components/logs/LogScaffold.vue";

defineOptions({ layout: AppLayout });

const page = usePage();
const no_pv = (page.props as any).no_pv;

type LogItem = {
  id: number | string;
  at: string;
  user: string;
  role?: string;
  action: string;
  note?: string;
};

const logs = (page.props as any).logs as LogItem[];

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-voucher" },
  { label: "Log Aktivitas" },
];

const getActionDescription = (action: string) => baseGetDesc(action, "Payment Voucher");

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

function getActivityColor(action: string) {
  return baseGetColor(action);
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
    router.visit("/payment-voucher");
  }
}
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

.bg-green-600 {
  background-color: #16a34a !important;
}

.bg-red-600 {
  background-color: #dc2626 !important;
}

.bg-yellow-600 {
  background-color: #ca8a04 !important;
}

.dot-glow {
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2),
    0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
