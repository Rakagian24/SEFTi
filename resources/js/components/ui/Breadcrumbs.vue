<template>
  <nav class="mb-3 sm:mb-4">
    <ol
      class="flex items-center space-x-1 sm:space-x-2 bg-[#EFF6F9] rounded-xl px-3 py-1.5 sm:px-4 sm:py-2 shadow text-xs sm:text-sm"
    >
      <li>
        <a href="/dashboard" class="text-gray-500 hover:text-gray-700">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="w-4 h-4 sm:w-5 sm:h-5"
          >
            <path
              d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"
            />
            <path
              d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"
            />
          </svg>
        </a>
      </li>
      <template v-for="(crumb, idx) in items" :key="idx">
        <li v-if="idx !== 0">
          <span class="mx-0.5 sm:mx-1 text-gray-400">&gt;</span>
        </li>
        <li>
          <span v-if="!crumb.href" class="font-semibold text-gray-900">{{
            crumb.label
          }}</span>
          <a v-else :href="crumb.href" class="text-gray-500 hover:text-gray-700">{{
            crumb.label
          }}</a>
        </li>
      </template>
    </ol>
  </nav>
</template>

<script setup lang="ts">
defineProps<{ items: { label: string; href?: string }[] }>();
</script>
