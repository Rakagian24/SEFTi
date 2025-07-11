<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { ChevronRight } from 'lucide-vue-next'
import { useSidebar } from './utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn(
      'absolute top-[80%] right-[-40px] z-[9999] rounded-full bg-white shadow-md border border-gray-200 hover:bg-gray-100 transition-transform -translate-y-1/2 h-6 w-6 p-0 flex items-center justify-center',
      props.class
    )"
    @click="toggleSidebar"
  >
    <ChevronRight class="text-black" />
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>
