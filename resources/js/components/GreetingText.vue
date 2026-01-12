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
  <h1
    class="font-normal text-gray-800 font-cursive text-xl sm:text-2xl lg:text-3xl leading-tight break-words max-w-[200px] sm:max-w-[230px] md:max-w-none"
  >
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
