<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Log Activity Memo Pembayaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <History class="w-4 h-4 mr-1" />
            Riwayat aktivitas dokumen Memo Pembayaran
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="goBack"
            class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeft class="w-4 h-4" />
            Kembali
          </button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <!-- Document Info -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Dokumen</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <span class="text-sm font-medium text-gray-500">No. MB:</span>
              <span class="text-sm text-gray-900 ml-2">{{ memoPembayaran.no_mb || '-' }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-500">Status:</span>
              <span
                :class="getStatusClass(memoPembayaran.status)"
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-2"
              >
                {{ memoPembayaran.status }}
              </span>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-500">Perihal:</span>
              <span class="text-sm text-gray-900 ml-2">{{ memoPembayaran.perihal?.nama || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Activity Log -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Riwayat Aktivitas</h3>
          <div class="space-y-4">
            <div
              v-for="log in logs"
              :key="log.id"
              class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg"
            >
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <Activity class="w-4 h-4 text-blue-600" />
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-medium text-gray-900">{{ log.description }}</p>
                  <p class="text-sm text-gray-500">{{ formatDateTime(log.created_at) }}</p>
                </div>
                <p class="text-sm text-gray-600 mt-1">
                  Oleh: <span class="font-medium">{{ log.user?.name || 'Unknown' }}</span>
                </p>

                <!-- Show changes if available -->
                <div v-if="log.old_values || log.new_values" class="mt-3">
                  <div v-if="log.old_values" class="mb-2">
                    <span class="text-xs font-medium text-red-600">Nilai Sebelumnya:</span>
                    <pre class="text-xs text-gray-600 mt-1 bg-red-50 p-2 rounded">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                  </div>
                  <div v-if="log.new_values">
                    <span class="text-xs font-medium text-green-600">Nilai Baru:</span>
                    <pre class="text-xs text-gray-600 mt-1 bg-green-50 p-2 rounded">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="logs.length === 0" class="text-center py-8">
              <History class="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p class="text-gray-500">Belum ada aktivitas yang tercatat</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { History, ArrowLeft, Activity } from "lucide-vue-next";
import { ref } from "vue";
const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Memo Pembayaran", href: "/memo-pembayaran" },
  { label: "Log Activity" }
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  memoPembayaran: any;
  logs: any[];
}>();

const memoPembayaran = ref(props.memoPembayaran);
const logs = ref(props.logs || []);

function goBack() {
  router.visit('/memo-pembayaran');
}

function formatDateTime(dateTime: string) {
  if (!dateTime) return '-';
  return new Date(dateTime).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getStatusClass(status: string) {
  switch (status) {
    case 'Draft':
      return 'bg-gray-100 text-gray-800';
    case 'In Progress':
      return 'bg-blue-100 text-blue-800';
    case 'Approved':
      return 'bg-green-100 text-green-800';
    case 'Rejected':
      return 'bg-red-100 text-red-800';
    case 'Canceled':
      return 'bg-yellow-100 text-yellow-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}
</script>
