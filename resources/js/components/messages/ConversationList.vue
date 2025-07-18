<template>
  <div class="conversation-list">
    <!-- Search bar -->
    <input type="text" placeholder="Cari percakapan/kontak..." v-model="search" />
    <!-- Sort dropdown -->
    <select v-model="sort">
      <option value="latest">Terbaru</option>
      <option value="oldest">Terlama</option>
    </select>
    <!-- New Chat button -->
    <button @click="$emit('newChat')">New Chat</button>
    <!-- List percakapan -->
    <div v-for="conversation in conversations" :key="conversation.id">
      <ConversationItem :conversation="conversation" @click="$emit('selectConversation', conversation)" />
    </div>
    <!-- Pagination (placeholder) -->
    <div class="pagination">Pagination</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ConversationItem from './ConversationItem.vue'
const search = ref('')
const sort = ref('latest')
const conversations = ref([]) // TODO: fetch from API
</script>

<style scoped>
.conversation-list {
  width: 350px;
  border-right: 1px solid #eee;
  padding: 1rem;
  overflow-y: auto;
}
</style>
