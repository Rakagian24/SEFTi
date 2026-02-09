<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <LogScaffold
        :breadcrumbs="breadcrumbs"
        headerTitle="Memo Pembayaran Activity Details"
        :infoTitle="`${memoPembayaran.no_mb} Activities`"
        infoSubtitle="Riwayat aktivitas untuk Memo Pembayaran"
      >
      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
                <p class="text-sm text-gray-600">
                  <template v-if="log.user">
                    Oleh {{ log.user.name }} - {{ displayUserRole(log.user) }}
                  </template>
                  <template v-else>Oleh System</template>
                </p>
              </div>
            </div>

            <!-- Kolom 2: Activity Icon + Timeline (desktop/tablet only) -->
            <div class="hidden md:flex items-center justify-start gap-12 relative">
              <!-- Activity Icon -->
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg',
                  getActivityColor(log.description),
                  index === 0 ? 'dot-glow' : '',
                ]"
              >
                <component :is="getActivityIcon(log.description)" class="w-5 h-5" />
              </div>

              <!-- Timeline Section -->
              <div class="flex flex-col items-center relative">
                <!-- Timeline Dot -->
                <div :class="getDotClass(Number(index))"></div>

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
          <div v-if="!logs || logs.length === 0" class="text-center py-12 col-span-3">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">
              There are no activities recorded for this memo pembayaran.
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
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Kembali ke Memo Pembayaran
        </button>
      </div>
      </LogScaffold>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Activity } from "lucide-vue-next";
import LogScaffold from "@/components/logs/LogScaffold.vue";
import { getActionDescription as baseGetDesc, getActivityIcon as baseGetIcon, getActivityColor as baseGetColor } from "@/lib/activity";
import { transformRoleLabel } from "@/lib/roleUtils";
// import { getMemoActionDescription } from "@/lib/actionDescriptions";

defineOptions({ layout: AppLayout });

const props = defineProps({
  memoPembayaran: { type: Object as any, required: true },
  logs: { type: Array as any, default: () => [] },
});

const docDeptName = (props.memoPembayaran as any)?.department?.name ?? "";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Memo Pembayaran", href: "/memo-pembayaran" },
  { label: "Log Aktivitas" },
];

const getActionDescription = (action: string) => baseGetDesc(action, "Memo Pembayaran");
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

// function getStatusClass(status: string) {
//   switch (status?.toLowerCase()) {
//     case "approved":
//     case "disetujui":
//       return "bg-green-100 text-green-800";
//     case "rejected":
//     case "ditolak":
//       return "bg-red-100 text-red-800";
//     case "pending":
//     case "menunggu":
//       return "bg-yellow-100 text-yellow-800";
//     case "draft":
//       return "bg-gray-100 text-gray-800";
//     default:
//       return "bg-gray-100 text-gray-800";
//   }
// }

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/memo-pembayaran");
  }
}

function displayUserRole(user: any): string {
  if (!user) return "";
  const roleName = user.role?.name || "";
  return transformRoleLabel(roleName, docDeptName);
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

.bg-blue-600 {
  background-color: #2563eb !important;
}

.dot-glow {
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2),
    0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
