<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { getIconForPage } from "@/lib/iconMapping";
import { RefreshCw, CheckCircle, FileText } from 'lucide-vue-next';
import BankMatchingFilter from '@/components/bank-matching/BankMatchingFilter.vue';
import AutoMatchingTab from '@/components/bank-matching/AutoMatchingTab.vue';
import MatchedDataTab from '@/components/bank-matching/MatchedDataTab.vue';
import AllInvoiceTab from '@/components/bank-matching/AllInvoiceTab.vue';

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Matching" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const page = usePage();
const matchingResults = (page.props.matchingResults as any[]) || [];
const filters = ref((page.props.filters as any) || {});
const error = ref(page.props.error || '');

// Tab management
const activeTab = ref('auto-matching');

const tabs = [
  { id: 'auto-matching', label: 'Auto Matching', icon: RefreshCw },
  { id: 'matched-data', label: 'Data Match', icon: CheckCircle },
  { id: 'all-invoices', label: 'All Invoice', icon: FileText }
];

function switchTab(tabId: string) {
  activeTab.value = tabId;
}

function handleFilterChanged(newFilters: any) {
  filters.value = { ...filters.value, ...newFilters };
}

// Watch for error from backend
watch(() => page.props.error, (newError) => {
  if (newError && typeof newError === 'string') {
    addError(newError);
  }
}, { immediate: true });
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bank Matching</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <component :is="getIconForPage('Bank Matching')" class="w-4 h-4 mr-1" />
            Matching data Invoice dengan Bank Masuk
          </div>
        </div>
      </div>

    <!-- Filter Section -->
      <BankMatchingFilter
        :filters="filters"
        @filter-changed="handleFilterChanged"
      />

      <!-- Tab Navigation -->
      <div class="bg-[#FFFFFF] border-t border-gray-200">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="switchTab(tab.id)"
              :class="[
                activeTab === tab.id
                  ? 'border-[#101010] text-[#101010]'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
              ]"
            >
              <component :is="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Auto Matching Tab -->
          <div v-if="activeTab === 'auto-matching'" class="space-y-4">
            <AutoMatchingTab
              :matching-results="matchingResults"
              :filters="filters"
            />
    </div>

          <!-- Matched Data Tab -->
          <div v-else-if="activeTab === 'matched-data'" class="space-y-4">
            <MatchedDataTab
              :filters="filters"
            />
    </div>

          <!-- All Invoice Tab -->
          <div v-else-if="activeTab === 'all-invoices'" class="space-y-4">
            <AllInvoiceTab
              :filters="filters"
            />
    </div>
      </div>
    </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for tab navigation */
.tab-button {
  transition: all 0.2s ease-in-out;
}

.tab-button:hover {
  transform: translateY(-1px);
}
</style>
