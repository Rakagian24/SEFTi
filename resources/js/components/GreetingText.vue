<script setup lang="ts">
interface Props {
  userName: string;
  greeting?: string;
}

withDefaults(defineProps<Props>(), {
  greeting: 'Good Morning'
});
</script>

<template>
  <h1 class="text-xl font-bold text-gray-800 font-cursive">
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
