<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { Activity } from 'lucide-vue-next';
import { getActionDescription, getActivityIcon, getActivityColor } from '@/lib/activity';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any; logs: any; filters: any }>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: `${props.bpb?.no_bpb || `BPB #${props.bpb?.id}`} - Log Activity` },
];

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
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
        <div class="space-y-0">
          <div v-for="(log, index) in logs && logs.data ? logs.data : []" :key="log.id" class="relative grid grid-cols-3 gap-6 py-4 hover:bg-gray-50 rounded-lg transition-colors duration-200">
            <div class="flex items-center">
              <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-900 capitalize mb-1">
                  {{ getActionDescription(log.action, 'BPB') }}
                </h3>
                <p class="text-sm text-gray-600">
                  <template v-if="log.user">
                    Oleh {{ log.user.name }} - {{ log.user.role ? log.user.role.name : '' }}
                  </template>
                  <template v-else>Oleh System</template>
                </p>
              </div>
            </div>

            <div class="flex items-center justify-start gap-12 relative">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg', getActivityColor(log.action, index), index === 0 ? 'dot-glow' : '']">
                <component :is="getActivityIcon(log.action)" class="w-5 h-5" />
              </div>
              <div class="flex flex-col items-center relative">
                <div :class="index === 0 ? 'w-4 h-4 rounded-full bg-blue-600 border-2 border-blue-600 dot-glow' : 'w-4 h-4 rounded-full border-2 border-gray-400 bg-white'"></div>
                <div v-if="logs && logs.data && index !== logs.data.length - 1" class="w-0.5 h-16 bg-gray-200 absolute top-4"></div>
              </div>
            </div>

            <div class="flex items-center justify-end">
              <div class="text-right">
                <div class="text-sm text-gray-500">{{ formatDateTime(log.created_at) }}</div>
              </div>
            </div>
          </div>

          <div v-if="!logs?.data || logs.data.length === 0" class="text-center py-12 col-span-3">
            <Activity class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">No Activities Found</h3>
            <p class="text-gray-500">There are no activities recorded for this BPB.</p>
          </div>
        </div>

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
    </div>
  </div>
</template>

<style scoped>
.dot-glow {
  box-shadow: 0 0 0 0px rgba(37, 99, 235, 0), 0 0 16px 8px rgba(37, 99, 235, 0.2), 0 0 24px 12px rgba(37, 99, 235, 0.12), 0 0 40px 20px rgba(37, 99, 235, 0.08);
}
</style>
