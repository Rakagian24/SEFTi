<template>
  <div class="chat-window" v-if="conversation">
    <div class="chat-header">Percakapan dengan {{ conversation.user }}</div>
    <div class="chat-messages">
      <div v-for="message in messages" :key="message.id" class="chat-message">
        <div>{{ message.sender }}</div>
        <div>{{ message.text }}</div>
        <MessageOptions :message="message" />
      </div>
    </div>
    <ChatInput :conversation="conversation" @send="onSend" />
  </div>
  <div v-else class="chat-empty">Pilih percakapan untuk mulai chat</div>
</template>

<script setup>
import { ref, watch } from 'vue'
import ChatInput from './ChatInput.vue'
import MessageOptions from './MessageOptions.vue'
const props = defineProps({
  conversation: Object
})
const messages = ref([]) // TODO: fetch from API
function onSend(msg) {
  // TODO: send message to API
}
watch(() => props.conversation, () => {
  // TODO: fetch messages for selected conversation
})
</script>

<style scoped>
.chat-window {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
}
.chat-header {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  font-weight: bold;
}
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}
.chat-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #aaa;
}
</style>
