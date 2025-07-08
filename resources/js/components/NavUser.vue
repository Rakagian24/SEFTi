<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type User } from '@/types';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

interface Props {
  user: User;
}

defineProps<Props>();
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button class="flex items-center gap-2 p-2 hover:bg-white/50 rounded-lg transition-colors">
        <UserInfo :user="user" />
        <ChevronsUpDown class="ml-auto size-4" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent
      class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
      side="bottom"
      align="end"
      :side-offset="4"
    >
      <UserMenuContent :user="user" />
    </DropdownMenuContent>
  </DropdownMenu>
</template>
