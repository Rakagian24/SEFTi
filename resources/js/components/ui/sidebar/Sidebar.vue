<script setup lang="ts">
import type { SidebarProps } from '.'
import { cn } from '@/lib/utils'
import { Sheet, SheetContent } from '@/components/ui/sheet'
import SheetDescription from '@/components/ui/sheet/SheetDescription.vue'
import SheetHeader from '@/components/ui/sheet/SheetHeader.vue'
import SheetTitle from '@/components/ui/sheet/SheetTitle.vue'
import { SIDEBAR_WIDTH_MOBILE, useSidebar } from './utils'
import SidebarTrigger from './SidebarTrigger.vue';

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(defineProps<SidebarProps>(), {
  side: 'left',
  variant: 'sidebar',
  collapsible: 'offcanvas',
})

const { isMobile, state, openMobile, setOpenMobile } = useSidebar()
</script>

<template>
  <div
    v-if="collapsible === 'none'"
    data-slot="sidebar"
    :class="cn('bg-sidebar text-sidebar-foreground flex h-full w-(--sidebar-width) flex-col', props.class)"
    v-bind="$attrs"
  >
    <slot />
  </div>

  <Sheet v-else-if="isMobile" :open="openMobile" v-bind="$attrs" @update:open="setOpenMobile">
    <SheetContent
      data-sidebar="sidebar"
      data-slot="sidebar"
      data-mobile="true"
      :side="side"
      class="bg-sidebar text-sidebar-foreground w-(--sidebar-width) p-0 [&>button]:hidden"
      :style="{
        '--sidebar-width': SIDEBAR_WIDTH_MOBILE,
      }"
    >
      <SheetHeader class="sr-only">
        <SheetTitle>Sidebar</SheetTitle>
        <SheetDescription>Displays the mobile sidebar.</SheetDescription>
      </SheetHeader>
      <div class="flex h-full w-full flex-col">
        <slot />
      </div>
    </SheetContent>
  </Sheet>

  <div
    v-else
    class="group peer text-sidebar-foreground hidden md:block"
    data-slot="sidebar"
    :data-state="state"
    :data-collapsible="state === 'collapsed' ? collapsible : ''"
    :data-variant="variant"
    :data-side="side"
  >
    <!-- This is what handles the sidebar gap on desktop  -->
    <div
      :class="cn(
        'relative w-(--sidebar-width) bg-transparent transition-[width] duration-200 ease-linear',
        'group-data-[collapsible=offcanvas]:w-0',
        'group-data-[side=right]:rotate-180',
        variant === 'floating' || variant === 'inset'
          ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4)))]'
          : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon)',
      )"
    />
    <div
      :class="cn(
        variant === 'floating'
          ? 'relative'
          : 'fixed inset-y-0 z-10 hidden h-svh w-(--sidebar-width) transition-[left,right,width] duration-200 ease-linear md:flex',
        side === 'left'
          ? 'left-0 group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)]'
          : 'right-0 group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)]',
        // Adjust the padding for floating and inset variants.
        variant === 'floating' || variant === 'inset'
          ? 'p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4))+2px)]'
          : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon) group-data-[side=left]:border-r group-data-[side=right]:border-l',
        props.class,
      )"
      v-bind="$attrs"
    >
      <div
        data-sidebar="sidebar"
        :class="cn(
          'bg-sidebar flex flex-col',
          variant === 'floating'
            ? 'my-[40px] ml-[24px] mr-0 mb-[40px] mt-[40px] rounded-t-[40px] rounded-b-[40px] min-w-[240px] w-[256px] shadow-lg relative min-h-[calc(100vh-80px)] h-fit'
            : 'h-full w-full',
          // Pastikan collapsed state memiliki width yang cukup untuk logo dengan bentuk tabung
          state === 'collapsed' && variant === 'floating' ? 'min-w-[80px] w-[80px] rounded-[40px]' : ''
        )"
      >
      <!-- <SidebarTrigger v-if="variant === 'floating'" class="absolute top-[25%] right-[-10px] -translate-y-1/2 z-20 bg-white text-black shadow-xl border border-gray-200 rounded-full" /> -->
        <slot />
      </div>
    </div>
  </div>
</template>

<style>
/* Override untuk memastikan logo tetap terlihat saat collapsed */
.group[data-variant="floating"] [data-sidebar="sidebar"] {
  position: static !important; /* Biarkan AppSidebar yang handle positioning */
  max-height: calc(100vh - 80px) !important;
  height: auto !important;
  overflow-y: auto !important;
}

.group[data-state="collapsed"] [data-sidebar="sidebar"] .sidebar-logo {
  opacity: 1 !important;
  visibility: visible !important;
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
}

.group[data-state="collapsed"] [data-sidebar="sidebar"] .sidebar-logo .logo-container {
  opacity: 1 !important;
  visibility: visible !important;
  display: flex !important;
  justify-content: center !important;
  width: 100% !important;
}

.group[data-state="collapsed"] [data-sidebar="sidebar"] .sidebar-logo .logo-text {
  opacity: 1 !important;
  visibility: visible !important;
  display: block !important;
  font-size: 1rem !important;
}


</style>
