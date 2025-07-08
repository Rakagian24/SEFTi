<script setup lang="ts">
// import Breadcrumbs from "@/components/Breadcrumbs.vue";
import { SidebarTrigger } from "@/components/ui/sidebar";
import GreetingText from "./GreetingText.vue";
import NotificationPanel from "./NotificationPanel.vue";
import NavUser from "./NavUser.vue";
import type { BreadcrumbItemType, User } from "@/types";
import { usePage } from '@inertiajs/vue3';
import { ref, computed } from "vue";

withDefaults(
  defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
  }>(),
  {
    breadcrumbs: () => [],
  }
);

// Get user from Inertia page props
const page = usePage();
const user = computed(() => page.props.auth.user as User);

// Mock notifications data - replace with real data from API/props
const notifications = ref({
  calendar: 3,
  messages: 5,
  alerts: 2
});

// Event handlers
const handleCalendarClick = () => {
  console.log('Calendar clicked');
  // Navigate to calendar or show calendar modal
  // You can use Inertia router here: router.visit('/calendar')
};

const handleMessageClick = () => {
  console.log('Messages clicked');
  // Navigate to messages or show message modal
  // router.visit('/messages')
};

const handleAlertClick = () => {
  console.log('Alerts clicked');
  // Navigate to alerts or show alert modal
  // router.visit('/notifications')
};

// Extract first name for greeting
const firstName = computed(() => {
  return user.value?.name?.split(' ')[0] || 'User';
});
</script>

<template>
  <header
    class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 bg-gradient-to-r from-blue-50 to-purple-50"
  >
    <!-- Left side - Greeting and Navigation -->
    <div class="flex items-center gap-4">
      <SidebarTrigger class="-ml-1" />

      <div class="flex items-center gap-3">
        <GreetingText :user-name="firstName" />

        <!-- Breadcrumbs -->
        <!-- <template v-if="breadcrumbs && breadcrumbs.length > 0">
          <div class="hidden md:block">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
          </div>
        </template> -->
      </div>
    </div>

    <!-- Right side - Notifications and Profile -->
    <div class="flex items-center gap-3">
      <NotificationPanel
        :notifications="notifications"
        @calendar-click="handleCalendarClick"
        @message-click="handleMessageClick"
        @alert-click="handleAlertClick"
      />

      <NavUser :user="user" />
    </div>
  </header>
</template>
