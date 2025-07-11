<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  userName: string;
}

// Tidak perlu deklarasi props jika tidak digunakan langsung

defineProps<Props>();

const greeting = computed(() => {
  const now = new Date();
  const utcHour = now.getUTCHours();
  const hour = (utcHour + 7) % 24; // GMT+7

  if (hour < 12) return 'Good Morning';
  if (hour < 18) return 'Good Afternoon';
  return 'Good Evening';
});
</script>

<template>
  <h1 class="text-3xl font-extrabold text-gray-800 font-cursive">
    {{ greeting }}, {{ userName }}!
  </h1>
</template>

<style scoped>
.font-cursive {
  font-family: 'Pacifico', cursive;
}

@supports not (font-family: 'Pacifico') {
  .font-cursive {
    font-family: 'Dancing Script', 'Pacifico', cursive;
  }
}
</style>
