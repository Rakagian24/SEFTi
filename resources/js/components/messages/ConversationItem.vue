<template>
  <div class="conversation-item" @click="$emit('click')">
    <div class="user-name">{{ conversation.user }}</div>
    <div class="last-message">{{ conversation.last_message }}</div>
    <div class="time">{{ conversation.time }}</div>
  </div>
</template>

<script setup>
const props = defineProps({
  conversation: Object
})
</script>

<style scoped>
.conversation-item {
  padding: 0.75rem 0.5rem;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
}
.conversation-item:hover {
  background: #f9f9f9;
}
.user-name {
  font-weight: bold;
}
.last-message {
  color: #888;
  font-size: 0.95em;
}
.time {
  font-size: 0.85em;
  color: #bbb;
  text-align: right;
}
</style>
