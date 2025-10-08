<script setup lang="ts">
import AppContent from "@/components/AppContent.vue";
import AppShell from "@/components/AppShell.vue";
import AppSidebar from "@/components/AppSidebar.vue";
import AppSidebarHeader from "@/components/AppSidebarHeader.vue";
import MessagePanel from "@/components/ui/MessagePanel.vue";
import GlobalAlertDialog from "@/components/GlobalAlertDialog.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import type { BreadcrumbItemType } from "@/types";

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});

const { messages } = useMessagePanel();
</script>

<template>
  <AppShell variant="sidebar">
    <div class="sidebar-layout-flex">
      <AppSidebar />
      <!-- <SidebarTrigger class="fixed top-1/4 left-[278px] -translate-y-1/2 z-[9999]" /> -->
      <AppContent variant="sidebar" class="main-content overflow-x-hidden">
        <AppSidebarHeader :breadcrumbs="breadcrumbs" />
        <slot />
      </AppContent>
    </div>

    <!-- Global Message Panel -->
    <MessagePanel :messages="messages" position="top-right" />

    <!-- Global Alert Dialog -->
    <GlobalAlertDialog />
  </AppShell>
</template>

<style scoped>
.sidebar-layout-flex {
  display: flex;
  align-items: flex-start;
  min-height: 100vh;
  height: auto;
  width: 100vw;
  max-width: 100vw;
  overflow-x: hidden;
}

:global(.app-shell) {
  min-height: 100vh;
  height: auto;
}

/* Pastikan sidebar mengikuti tinggi konten utama */
/* Hapus style sticky/floating pada sidebar agar tidak bentrok dengan fixed sidebar di AppSidebar.vue */
/*
:global(.group[data-variant="floating"]) {
  height: auto !important;
  min-height: 100vh;
  align-self: stretch;
}
:global(.group[data-variant="floating"] [data-sidebar="sidebar"]) {
  height: auto !important;
  min-height: calc(100vh - 80px) !important;
  max-height: calc(100vh - 80px) !important;
  position: sticky !important;
  top: 40px !important;
  align-self: flex-start;
}
*/

/* Pastikan main content mengatur tinggi layout */
.main-content {
  flex: 1;
  min-height: 100vh;
  height: auto;
}

/* Pastikan main tag mengatur tinggi layout */
:global(main) {
  flex: 1;
  min-height: 100vh;
  height: auto;
}
</style>
