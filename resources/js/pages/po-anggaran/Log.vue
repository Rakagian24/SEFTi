<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <LogScaffold
        :breadcrumbs="breadcrumbs"
        headerTitle="PO Anggaran Activity Details"
        :infoTitle="`${ poAnggaran?.no_po_anggaran || 'PO Anggaran' } Activities`"
        infoSubtitle="Riwayat aktivitas untuk PO Anggaran"
      >
      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <!-- Activity Items -->
          <div
            v-for="(log, index) in logsList"
            :key="log.id || index"
            class="relative grid grid-cols-3 gap-6 py-4 hover:bg-gray-50 rounded-lg transition-colors duration-200"
          >
            <!-- Col 1: Activity Item -->
            <div class="flex items-center">
              <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-900 capitalize mb-1">
                  {{ getActionDescription(log.action) }}
                </h3>
                <p class="text-sm text-gray-600">Oleh {{ log.created_by ? ('User ID ' + log.created_by) : 'System' }}</p>
              </div>
            </div>

            <!-- Col 2: Activity Icon + Timeline -->
            <div class="flex items-center justify-start gap-12 relative">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg', getActivityColor(log.action), index === 0 ? 'dot-glow' : '']">
                <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
              </div>

              <div class="flex flex-col items-center relative">
                <div :class="getDotClass(index)"></div>
                <div v-if="index !== logsList.length - 1" class="w-0.5 h-16 bg-gray-200 absolute top-4"></div>
              </div>
            </div>

            <!-- Col 3: Timestamp -->
            <div class="flex items-center justify-end">
              <div class="text-right">
                <div class="text-sm text-gray-500">{{ formatDateTime(log.created_at) }}</div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!logsList || logsList.length === 0" class="text-center py-12 col-span-3">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">There are no activities recorded for this PO Anggaran.</p>
          </div>
        </div>

        <!-- No pagination: backend returns non-paginated logs -->
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Kembali ke PO Anggaran
        </button>
      </div>
      </LogScaffold>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Activity } from 'lucide-vue-next';
import { getActionDescription as baseGetDesc, getActivityIcon as baseGetIcon, getActivityColor as baseGetColor } from '@/lib/activity';
import LogScaffold from '@/components/logs/LogScaffold.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
  poAnggaran: Object,
  logs: { type: [Object, Array], default: () => [] },
});

const logsList = computed<any[]>(() => {
  const value = props.logs as any;
  return Array.isArray(value) ? value : value?.data ?? [];
});
const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'PO Anggaran', href: '/po-anggaran' },
  { label: 'Log Aktivitas' },
];

const getActionDescription = (action: string) => baseGetDesc(action, 'PO Anggaran');
const getActivityIcon = (action: string) => baseGetIcon(action);
const getActivityColor = (action: string) => baseGetColor(action);

function formatDateTime(dateString: string) {
  const date = new Date(dateString);
  const tanggal = date.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
  const jam = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  return `${tanggal} - ${jam}`;
}

function getDotClass(index: number) {
  if (index === 0) return 'w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow';
  return 'w-4 h-4 rounded-full border-2 border-gray-400 bg-white';
}

function goBack() {
  if (window.history.length > 1) window.history.back();
  else router.visit('/po-anggaran');
}

// No filters/pagination: backend returns full log list
</script>

<style scoped>
/* Responsive design */
@media (max-width: 768px) {
  .grid-cols-3 { grid-template-columns: 1fr; gap: 1rem; }
  .text-right { text-align: left !important; }
  .justify-end { justify-content: flex-start !important; }
}

/* Activity icon animations */
.w-10.h-10 { transition: all 0.3s ease; }
.w-10.h-10:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

/* Pagination enhancements */
nav button:focus { outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
nav button:disabled { opacity: 0.5; }
nav button:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

/* Timeline line styling */
.bg-gray-200 { background: linear-gradient(to bottom, #e5e7eb, #f3f4f6); }

.bg-blue-600 { background-color: #2563eb !important; }
.dot-glow { box-shadow: 0 0 0 0px rgba(37,99,235,0), 0 0 16px 8px rgba(37,99,235,0.2), 0 0 24px 12px rgba(37,99,235,0.12), 0 0 40px 20px rgba(37,99,235,0.08); }
</style>
