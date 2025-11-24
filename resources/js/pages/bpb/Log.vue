<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Activity } from 'lucide-vue-next';
import { getActionDescription, getActivityIcon, getActivityColor } from '@/lib/activity';
import { transformRoleLabel } from '@/lib/roleUtils';
import LogScaffold from '@/components/logs/LogScaffold.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any; logs: any; filters: any }>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: `Log Aktivitas` },
];

const docDeptName = (props.bpb as any)?.department?.name ?? '';

const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || '');
const actionFilter = ref(props.filters?.action || '');
const dateFilter = ref(props.filters?.date || '');

watch([entriesPerPage, actionFilter, dateFilter], () => applyFilters());

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), 500);
});

function applyFilters() {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;

  router.get(`/bpb/${props.bpb?.id}/log`, params, { preserveState: true, preserveScroll: true });
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/bpb");
  }
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  const params: Record<string, any> = { page };
  if (searchQuery.value) params.search = searchQuery.value;
  if (actionFilter.value) params.action = actionFilter.value;
  if (dateFilter.value) params.date = dateFilter.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  router.get(`/bpb/${props.bpb?.id}/log`, params, { preserveState: true, preserveScroll: true });
}

function prevPage() { handlePagination(props.logs?.prev_page_url ?? ''); }
function nextPage() { handlePagination(props.logs?.next_page_url ?? ''); }

function formatDateTime(dateString: string) {
  const date = new Date(dateString);
  const tanggal = date.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
  const jam = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  return `${tanggal} - ${jam}`;
}

function getDotClass(index: number) {
  if (index === 0) {
    return 'w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow';
  }
  return 'w-4 h-4 rounded-full border-2 border-gray-400 bg-white';
}

function displayUserRole(user: any): string {
  if (!user) return '';
  const roleName = user.role?.name || '';
  return transformRoleLabel(roleName, docDeptName);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <LogScaffold
        :breadcrumbs="breadcrumbs"
        headerTitle="BPB Activity Details"
        infoTitle="BPB Activities"
        :infoSubtitle="`Riwayat aktivitas untuk BPB ${ props.bpb?.no_bpb ? `#${props.bpb.no_bpb}` : `#${props.bpb?.id}` }`"
      >
      <!-- Activity Timeline Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <!-- Activity Items -->
          <div v-for="(log, index) in logs && logs.data ? logs.data : []" :key="log.id" class="relative grid grid-cols-3 gap-6 py-4 hover:bg-gray-50 rounded-lg transition-colors duration-200">
            <!-- Kolom 1: Activity Item -->
            <div class="flex items-center">
              <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-900 capitalize mb-1">
                  {{ getActionDescription(log.action, 'BPB') }}
                </h3>
                <p class="text-sm text-gray-600">
                  <template v-if="log.user">
                    Oleh {{ log.user.name }} - {{ displayUserRole(log.user) }}
                  </template>
                  <template v-else>Oleh System</template>
                </p>
              </div>
            </div>

            <!-- Kolom 2: Activity Icon + Timeline -->
            <div class="flex items-center justify-start gap-12 relative">
              <!-- Activity Icon -->
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg', getActivityColor(log.action), index === 0 ? 'dot-glow' : '']">
                <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
              </div>

              <!-- Timeline Section -->
              <div class="flex flex-col items-center relative">
                <!-- Timeline Dot -->
                <div :class="getDotClass(index)"></div>

                <!-- Timeline Line -->
                <div v-if="logs && logs.data && index !== logs.data.length - 1" class="w-0.5 h-16 bg-gray-200 absolute top-4"></div>
              </div>
            </div>

            <!-- Kolom 3: Timestamp -->
            <div class="flex items-center justify-end">
              <div class="text-right">
                <div class="text-sm text-gray-500">{{ formatDateTime(log.created_at) }}</div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!logs?.data || logs.data.length === 0" class="text-center py-12 col-span-3">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">There are no activities recorded for this BPB.</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="logs?.data && logs.data.length > 0" class="mt-8 flex items-center justify-center border-t border-gray-200 pt-6">
          <nav class="flex items-center space-x-2" aria-label="Pagination">
            <button @click="prevPage" :disabled="!logs?.prev_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', logs?.prev_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">Previous</button>
            <template v-for="(link, index) in logs?.links?.slice(1, -1)" :key="index">
              <button @click="handlePagination(link.url)" :disabled="!link.url" :class="['w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200', link.active ? 'bg-black text-white' : link.url ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-gray-200 text-gray-400 cursor-not-allowed']" v-html="link.label"></button>
            </template>
            <button @click="nextPage" :disabled="!logs?.next_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', logs?.next_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">Next</button>
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
          Kembali ke BPB
        </button>
      </div>
      </LogScaffold>
    </div>
  </div>
</template>

<style scoped>
/* Timeline enhancements */
.hover\:bg-gray-50:hover {
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
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2), 0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
