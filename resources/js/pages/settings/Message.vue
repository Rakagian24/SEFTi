<template>
  <div class="message-page">
    <ConversationList @selectConversation="selectConversation" @newChat="openNewChat" />
    <ChatWindow :conversation="selectedConversation" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ConversationList from '@/components/messages/ConversationList.vue'
import ChatWindow from '@/components/messages/ChatWindow.vue'

const selectedConversation = ref(null)
function selectConversation(conversation) {
  selectedConversation.value = conversation
}
function openNewChat() {
  // TODO: open new chat popup
}
</script>

<style scoped>
.message-page {
  display: flex;
  height: 100vh;
}
</style>
