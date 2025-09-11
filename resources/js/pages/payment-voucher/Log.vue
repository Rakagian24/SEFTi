<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            Payment Voucher Activity Details
          </h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Activity class="w-4 h-4 mr-1" />
            These are the activities that have been recorded.
          </div>
        </div>
      </div>

      <!-- Payment Voucher Info Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 bg-[#7F9BE6] rounded-full flex items-center justify-center"
          >
            <FileText class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              Payment Voucher Activities
            </h3>
            <p class="text-sm text-gray-500">
              Riwayat aktivitas untuk Payment Voucher #{{ id }}
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
                  {{ getActionDescription(log.action) }} #{{ id }}
                </h3>
                <p class="text-sm text-gray-600">Oleh {{ log.user }}</p>
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
                  {{ formatDateTime(log.at) }}
                </div>
                <div v-if="log.note" class="text-xs text-gray-600 mt-1">
                  {{ log.note }}
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";
import {
  Activity,
  Plus,
  Edit,
  Trash2,
  ArrowRight,
  FileText,
  Check,
  X,
  Send,
  Eye,
} from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";

defineOptions({ layout: AppLayout });

const page = usePage();
const id = (page.props as any).id;

type LogItem = {
  id: number | string;
  at: string;
  user: string;
  action: string;
  note?: string;
};

const logs = (page.props as any).logs as LogItem[];

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-vouchers" },
  { label: "Log Aktivitas" },
];

function getActionDescription(action: string) {
  switch (action.toLowerCase()) {
    case "created":
    case "create":
      return "Membuat Payment Voucher";
    case "updated":
    case "update":
      return "Mengubah Payment Voucher";
    case "deleted":
    case "delete":
      return "Menghapus Payment Voucher";
    case "approved":
    case "approve":
      return "Menyetujui Payment Voucher";
    case "rejected":
    case "reject":
      return "Menolak Payment Voucher";
    case "submitted":
    case "submit":
      return "Mengirim Payment Voucher";
    case "reviewed":
    case "review":
      return "Meninjau Payment Voucher";
    case "processed":
    case "process":
      return "Memproses Payment Voucher";
    case "paid":
    case "pay":
      return "Membayar Payment Voucher";
    case "cancelled":
    case "cancel":
      return "Membatalkan Payment Voucher";
    case "viewed":
    case "view":
      return "Melihat Payment Voucher";
    default:
      return action;
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
    case "approved":
    case "approve":
      return Check;
    case "rejected":
    case "reject":
      return X;
    case "submitted":
    case "submit":
      return Send;
    case "reviewed":
    case "review":
      return Eye;
    case "processed":
    case "process":
      return ArrowRight;
    case "paid":
    case "pay":
      return Check;
    case "cancelled":
    case "cancel":
      return X;
    case "viewed":
    case "view":
      return Eye;
    default:
      return Activity;
  }
}

function getActivityColor(action: string, index: number) {
  if (index === 0) {
    switch (action.toLowerCase()) {
      case "approved":
      case "paid":
        return "bg-green-600";
      case "rejected":
      case "cancelled":
        return "bg-red-600";
      case "submitted":
      case "processed":
        return "bg-blue-600";
      case "reviewed":
      case "viewed":
        return "bg-yellow-600";
      default:
        return "bg-blue-600";
    }
  }
  return "bg-gray-400";
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
    router.visit("/payment-vouchers");
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
