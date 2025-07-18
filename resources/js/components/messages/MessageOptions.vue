<template>
  <div class="message-options">
    <button @click="show = !show">⋮</button>
    <div v-if="show" class="dropdown">
      <button @click="$emit('edit')">Edit</button>
      <button @click="$emit('delete')">Hapus</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const props = defineProps({
  message: Object
})
const show = ref(false)
</script>

<style scoped>
.message-options {
  display: inline-block;
  position: relative;
}
.dropdown {
  position: absolute;
  right: 0;
  background: #fff;
  border: 1px solid #eee;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  z-index: 10;
}
.dropdown button {
  display: block;
  width: 100%;
  padding: 0.5rem 1rem;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
}
.dropdown button:hover {
  background: #f5f5f5;
}
</style>
