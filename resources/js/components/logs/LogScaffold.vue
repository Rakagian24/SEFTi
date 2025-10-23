<script setup lang="ts">
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { Activity, FileText } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  breadcrumbs: Array<{ label: string; href?: string }>,
  headerTitle: string,
  headerSubtitle?: string,
  infoTitle: string,
  infoSubtitle: string,
  icon?: any,
}>();

const headerSub = computed(() => props.headerSubtitle ?? 'These are the activities that have been recorded.');
const IconComp = computed(() => props.icon ?? FileText);
</script>

<template>
  <div>
    <Breadcrumbs :items="breadcrumbs" />

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ headerTitle }}</h1>
        <div class="flex items-center mt-2 text-sm text-gray-500">
          <Activity class="w-4 h-4 mr-1" />
          {{ headerSub }}
        </div>
      </div>
    </div>

    <!-- Info Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-[#7F9BE6] rounded-full flex items-center justify-center">
          <component :is="IconComp" class="w-6 h-6 text-white" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ infoTitle }}</h3>
          <p class="text-sm text-gray-500">{{ infoSubtitle }}</p>
        </div>
      </div>
    </div>

    <slot />
  </div>
</template>
