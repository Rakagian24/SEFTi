<template>
  <nav class="mb-4">
    <ol class="flex items-center space-x-2 bg-white rounded-xl px-4 py-2 shadow">
      <li>
        <a href="/" class="text-gray-500 hover:text-gray-700">
          <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8v8m-4 0h4"/>
          </svg>
        </a>
      </li>
      <template v-for="(crumb, idx) in items" :key="idx">
        <li v-if="idx !== 0">
          <span class="mx-1 text-gray-400">&gt;</span>
        </li>
        <li>
          <span v-if="!crumb.href" class="font-semibold text-gray-900">{{ crumb.label }}</span>
          <a v-else :href="crumb.href" class="text-gray-500 hover:text-gray-700">{{ crumb.label }}</a>
        </li>
      </template>
    </ol>
  </nav>
</template>

<script setup lang="ts">
defineProps<{ items: { label: string, href?: string }[] }>();
</script>
