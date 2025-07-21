<template>
  <AppLayout>
    <SettingsLayout>
      <div class="message-page">
        <ConversationList
          :selectedConversation="selectedConversation"
          @selectConversation="selectConversation"
          @newChat="openNewChat"
          ref="conversationList"
        />
        <ChatWindow :conversation="selectedConversation" />
        <NewChatDialog
          :show="showNewChat"
          @close="showNewChat = false"
          @created="onNewChatCreated"
        />
      </div>
    </SettingsLayout>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { ComponentPublicInstance } from 'vue'
import ConversationList from '@/components/messages/ConversationList.vue'
import ChatWindow from '@/components/messages/ChatWindow.vue'
import NewChatDialog from '@/components/messages/NewChatDialog.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
interface Conversation {
  id: number|string
  [key: string]: any
}
const selectedConversation = ref<Conversation|null>(null)
const showNewChat = ref(false)
const conversationList = ref<ComponentPublicInstance<{ fetchConversations: () => void }> | null>(null)

function selectConversation(conversation: Conversation) {
  selectedConversation.value = conversation
}

function openNewChat() {
  showNewChat.value = true
}

function onNewChatCreated({ conversation }: { conversation: Conversation }) {
  // Refresh list dan auto-select percakapan baru
  if (conversationList.value) {
    conversationList.value.fetchConversations()
  }
  selectedConversation.value = conversation
}
</script>

<style scoped>
.message-page {
  display: flex;
  height: 100vh;
  background: white;
}
</style>
