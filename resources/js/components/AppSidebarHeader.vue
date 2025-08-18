<script setup lang="ts">
// import Breadcrumbs from "@/components/Breadcrumbs.vue";
import GreetingText from "./GreetingText.vue";
import NotificationPanel from "./NotificationPanel.vue";
import NavUser from "./NavUser.vue";
// import DepartmentDropdown from "./DepartmentDropdown.vue";
import type { BreadcrumbItemType, User } from "@/types";
import { usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

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
  alerts: 2,
});

// Event handlers
const handleCalendarClick = () => {
  // Navigate to calendar or show calendar modal
  // You can use Inertia router here: router.visit('/calendar')
};

const handleMessageClick = () => {
  // Navigate to messages or show message modal
  // router.visit('/messages')
};

const handleAlertClick = () => {
  // Navigate to alerts or show alert modal
  // router.visit('/notifications')
};

// Extract first name for greeting
const firstName = computed(() => {
  return user.value?.name?.split(" ")[0] || "User";
});

const activeDepartment = ref("");
// const isRefreshing = ref(false);
const lastSelectedDept = ref("");

onMounted(() => {
  // Load department aktif dari URL saat mount
  const urlParams = new URLSearchParams(window.location.search);
  const urlDept = urlParams.get("activeDepartment") || "";
  lastSelectedDept.value = urlDept;
  activeDepartment.value = urlDept;
});

// function handleDepartmentChange(val: string) {
//   // Jika sama dengan yang sudah dipilih, jangan refresh
//   if (val === lastSelectedDept.value) return;

//   activeDepartment.value = val;
//   lastSelectedDept.value = val;

//   // Update URL tanpa reload
//   const url = new URL(window.location.href);
//   if (val) {
//     url.searchParams.set("activeDepartment", val);
//   } else {
//     url.searchParams.delete("activeDepartment");
//   }

//   // Update URL tanpa reload
//   window.history.pushState({}, "", url.toString());

//   // Refresh halaman
//   window.location.reload();
// }
</script>

<template>
  <header
    class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:p-4"
  >
    <!-- Left side - Greeting and Navigation -->
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-3">
        <GreetingText :user-name="firstName" />
      </div>
    </div>

    <!-- Middle - Department Dropdown -->
    <!-- <DepartmentDropdown @update:activeDepartment="handleDepartmentChange" /> -->

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
