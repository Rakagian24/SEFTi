<template>
  <div class="chat-window" v-if="conversation">
    <div class="chat-header">
      <div class="header-info">
        <div class="avatar">
          <img :src="avatarUrl" :alt="conversation.user" />
        </div>
        <div class="user-details">
          <h3 class="user-name">{{ conversation.user }}</h3>
          <span class="user-status">Online</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="action-btn">📞</button>
        <button class="action-btn">🎥</button>
        <button class="action-btn">ℹ️</button>
      </div>
    </div>

    <div class="chat-date">
      <span class="date-label">{{ currentDate }}</span>
    </div>

    <div class="chat-messages" ref="messagesContainer">
      <div v-for="message in messages" :key="message.id" class="message-wrapper">
        <div
          class="chat-message"
          :class="{ sent: message.is_sent, received: !message.is_sent }"
        >
          <div class="message-content">
            <p class="message-text">{{ message.text }}</p>
            <div class="message-meta">
              <span class="message-time">{{ formatTime(message.created_at) }}</span>
              <span v-if="message.is_sent" class="message-status">✓</span>
            </div>
          </div>
          <MessageOptions v-if="message.is_sent" :message="message" />
        </div>
      </div>
    </div>

    <ChatInput :conversation="conversation" @send="onSend" />
  </div>
  <div v-else class="chat-empty">
    <div class="empty-state">
      <h3>Pilih percakapan untuk mulai chat</h3>
      <p>Pilih salah satu percakapan dari daftar untuk memulai mengirim pesan</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from "vue";
import ChatInput from "./ChatInput.vue";
import MessageOptions from "./MessageOptions.vue";

interface Message {
  id: number | string;
  text: string;
  created_at: string;
  is_sent: boolean;
  sender?: string;
  [key: string]: any;
}

const props = defineProps<{ conversation?: { id: number | string; user: string } }>();
const messages = ref<Message[]>([]);
const messagesContainer = ref<HTMLElement | null>(null);
let echoChannel: any = null;

const currentDate = computed(() => {
  const today = new Date();
  return today.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
});

const avatarUrl = computed(() => {
  if (!props.conversation) return "";
  const name = props.conversation.user || "User";
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(
    name
  )}&size=40&background=random`;
});

function formatTime(timestamp: string) {
  if (!timestamp) return "";
  const date = new Date(timestamp);
  return date.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
}

function fetchMessages() {
  if (!props.conversation) return;
  fetch(`/api/settings/message/${props.conversation.id}`)
    .then(res => {
      if (!res.ok) throw new Error("Gagal memuat pesan");
      return res.json();
    })
    .then(res => {
      messages.value = res.messages.data.map((msg: any) => ({
        ...msg,
        is_sent: msg.sender === "You", // Adjust based on your logic
      }));
      scrollToBottom();
    });
}

function onSend({ message, file }: { message: string; file: File | null }) {
  const form = new FormData();
  form.append("message", message);
  if (file) form.append("attachment", file);

  fetch(`/api/settings/message/${props.conversation!.id}/send`, {
    method: "POST",
    body: form
  })
    .then(async res => {
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.message || "Gagal mengirim pesan.");
      }
      return res.json();
    })
    .then(res => {
      const newMessage = {
        ...res.message,
        is_sent: true,
      };
      messages.value.push(newMessage);
      scrollToBottom();
    });
}

function subscribeRealtime() {
  if (!props.conversation) return;
  echoChannel = window.Echo.private("conversation." + props.conversation.id).listen(
    "MessageSent",
    (e: any) => {
      const newMessage: Message = {
        ...e.message,
        is_sent: false,
      };
      messages.value.push(newMessage);
      scrollToBottom();
    }
  );
}

function unsubscribeRealtime() {
  if (echoChannel && props.conversation) {
    window.Echo.leave("conversation." + props.conversation.id);
    echoChannel = null;
  }
}

watch(
  () => props.conversation,
  (val, oldVal) => {
    if (oldVal) unsubscribeRealtime();
    if (val) {
      fetchMessages();
      subscribeRealtime();
    }
  }
);

onMounted(() => {
  if (props.conversation) {
    fetchMessages();
    subscribeRealtime();
  }
});

onUnmounted(() => {
  if (props.conversation) unsubscribeRealtime();
});
</script>

<style scoped>
.chat-window {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: white;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e9ecef;
  background: white;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-details h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.user-status {
  font-size: 12px;
  color: #28a745;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  border-radius: 4px;
  font-size: 16px;
}

.action-btn:hover {
  background: white;
}

.chat-date {
  text-align: center;
  padding: 16px;
  background: white;
}

.date-label {
  font-size: 12px;
  color: #666;
  background: white;
  padding: 4px 12px;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: white;
}

.message-wrapper {
  margin-bottom: 16px;
}

.chat-message {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}

.chat-message.sent {
  justify-content: flex-end;
}

.chat-message.received {
  justify-content: flex-start;
}

.message-content {
  max-width: 70%;
  padding: 12px 16px;
  border-radius: 18px;
  position: relative;
}

.chat-message.sent .message-content {
  background: #007bff;
  color: white;
  border-bottom-right-radius: 4px;
}

.chat-message.received .message-content {
  background: white;
  color: #333;
  border: 1px solid #e9ecef;
  border-bottom-left-radius: 4px;
}

.message-text {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
}

.message-meta {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
}

.message-time {
  font-size: 11px;
  opacity: 0.7;
}

.message-status {
  font-size: 12px;
  opacity: 0.7;
}

.chat-empty {
  width: 100%;
  height: 100%;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
}

.empty-state {
  text-align: center;
  color: #666;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  color: #333;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}
</style>
