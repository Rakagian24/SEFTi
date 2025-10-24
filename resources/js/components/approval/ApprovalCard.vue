<template>
  <div
    class="bg-[#D0E1E9] rounded-2xl p-6 cursor-pointer group hover:shadow-lg transition-all duration-200 relative"
    :class="{ 'ring-2 ring-blue-300 shadow-blue-200': isSelected }"
    @click="navigateToDetail"
  >
    <!-- Content with Icon and Count Display -->
    <div class="flex items-center gap-4">
      <!-- Icon -->
      <div
        class="w-12 h-12 bg-transparent flex items-center justify-center flex-shrink-0"
      >
        <component :is="getIconComponent()" class="w-6 h-6 text-black" />
      </div>

      <!-- Count Display -->
      <div class="flex-1">
        <div v-if="loading" class="animate-pulse">
          <div class="h-8 bg-gray-300 rounded w-16 mb-2"></div>
          <div class="h-4 bg-gray-300 rounded w-20"></div>
        </div>
        <div v-else>
          <div class="text-4xl font-bold text-black mb-1">
            {{ count }}
          </div>
          <div class="text-sm text-black font-normal">
            {{ title }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { getIconForPage } from "@/lib/iconMapping";

interface Props {
  title: string;
  count: number;
  icon?: string;
  color: string;
  href: string;
  loading?: boolean;
  isSelected?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  isSelected: false,
  icon: "",
});

// Get icon component based on icon name
const getIconComponent = () => {
  return getIconForPage(props.title);
};

// Navigate to detail page
const navigateToDetail = () => {
  router.visit(props.href);
};
</script>
