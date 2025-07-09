<template>
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
      <button class="absolute top-2 right-2 text-gray-500" @click="$emit('close')">
        <i class="fa fa-times"></i>
      </button>
      <h2 class="text-lg font-bold mb-4">Log Aktivitas Bank</h2>
      <ul v-if="logs.length">
        <li v-for="(log, idx) in logs" :key="idx" class="mb-2">
          <span class="font-semibold">{{ log.activity }}</span> oleh <span class="text-blue-600">{{ log.user }}</span> pada <span>{{ formatDate(log.date) }}</span>
        </li>
      </ul>
      <div v-else class="text-center text-gray-400">Belum ada aktivitas</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';
defineProps<{ logs: Array<{ activity: string, user: string, date: string }> }>();

function formatDate(date: string) {
  return new Date(date).toLocaleString();
}
</script>
