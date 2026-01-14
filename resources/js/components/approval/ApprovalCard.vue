<template>
  <div
    class="bg-[#D0E1E9] rounded-2xl p-4 sm:p-6 cursor-pointer group hover:shadow-lg transition-all duration-200 relative"
    :class="{ 'ring-2 ring-blue-300 shadow-blue-200': isSelected }"
    @click="navigateToDetail"
  >
    <!-- Content with Icon and Count Display -->
    <div class="flex items-center gap-3 sm:gap-4">
      <!-- Icon -->
      <div
        class="w-10 h-10 sm:w-12 sm:h-12 bg-transparent flex items-center justify-center flex-shrink-0"
      >
        <component :is="getIconComponent()" class="w-5 h-5 sm:w-6 sm:h-6 text-black" />
      </div>

      <!-- Count Display -->
      <div class="flex-1">
        <div v-if="loading" class="animate-pulse">
          <div class="h-7 sm:h-8 bg-gray-300 rounded w-14 sm:w-16 mb-1 sm:mb-2"></div>
          <div class="h-3.5 sm:h-4 bg-gray-300 rounded w-24"></div>
        </div>
        <div v-else>
          <div class="text-3xl sm:text-4xl font-bold text-black leading-tight mb-1">
            {{ count }}
          </div>
          <div class="text-xs sm:text-sm text-black font-normal leading-snug break-words">
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
