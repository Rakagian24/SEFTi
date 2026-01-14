<script setup lang="ts">

import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

type NavGroup = {
  label: string;
  items: NavItem[];
};

defineProps<{
  groups: NavGroup[];
}>();

const page = usePage();

// Track open/closed state per group label
const openGroups = ref<Record<string, boolean>>({});

const isGroupOpen = (label: string) => {
  if (openGroups.value[label] === undefined) {
    // default: all groups open
    openGroups.value[label] = true;
  }
  return openGroups.value[label];
};

const toggleGroup = (label: string) => {
  openGroups.value[label] = !isGroupOpen(label);
};

// Close mobile sidebar when navigating via menu item
const { isMobile, setOpenMobile } = useSidebar();

const handleItemClick = () => {
  if (isMobile?.value) {
    setOpenMobile(false);
  }
};
</script>

<template>
  <SidebarGroup
    v-for="group in groups"
    :key="group.label"
    class="px-2 py-0"
  >
    <SidebarGroupLabel
      class="flex items-center justify-between cursor-pointer select-none"
      @click="toggleGroup(group.label)"
    >
      <span>{{ group.label }}</span>
      <span class="ml-2 text-xs opacity-70">
        {{ isGroupOpen(group.label) ? '&#8211;' :
          '&#43;'
        }}
      </span>
    </SidebarGroupLabel>
    <transition name="sidebar-group-collapse">
      <SidebarMenu v-show="isGroupOpen(group.label)">
        <SidebarMenuItem v-for="item in group.items" :key="item.title">
          <SidebarMenuButton as-child :is-active="item.href === page.url" :tooltip="item.title">
            <Link :href="item.href" @click="handleItemClick">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </transition>
  </SidebarGroup>
</template>

<style scoped>
.sidebar-group-collapse-enter-active,
.sidebar-group-collapse-leave-active {
  transition: all 0.15s ease-out;
}

.sidebar-group-collapse-enter-from,
.sidebar-group-collapse-leave-to {
  max-height: 0;
  opacity: 0;
}

.sidebar-group-collapse-enter-to,
.sidebar-group-collapse-leave-from {
  max-height: 500px;
  opacity: 1;
}
</style>
