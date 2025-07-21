<template>
  <div class="conversation-item" :class="{ active: isSelected }" @click="$emit('click')">
    <div class="avatar">
      <img :src="avatarUrl" :alt="otherUserName" />
    </div>
    <div class="conversation-content">
      <div class="conversation-header">
        <h3 class="user-name">{{ otherUserName }}</h3>
        <span class="time">{{ formattedTime }}</span>
      </div>
      <p class="last-message">{{ lastMessage }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps({
  conversation: Object,
  isSelected: Boolean,
});

const otherUserName = computed(() => {
  if (!props.conversation) return "";
  return props.conversation.user_one?.name || props.conversation.userTwo?.name || "User";
});

const lastMessage = computed(() => {
  if (!props.conversation?.last_message?.message) return "PV dengan nomor tidak...";
  return props.conversation.last_message.message;
});

const formattedTime = computed(() => {
  if (!props.conversation?.last_message?.created_at) return "8:00 am";
  const date = new Date(props.conversation.last_message.created_at);
  return date.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
});

const avatarUrl = computed(() => {
  // Generate avatar based on user name or use default
  const name = otherUserName.value;
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(
    name
  )}&size=40&background=random`;
});
</script>

<style scoped>
.conversation-item {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  border-bottom: 1px solid #f0f0f0;
}

.conversation-item:hover {
  background-color: white;
}

.conversation-item.active {
  background-color: #e3f2fd;
  border-right: 3px solid #2196f3;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  margin-right: 12px;
  flex-shrink: 0;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.conversation-content {
  flex: 1;
  min-width: 0;
}

.conversation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin: 0;
  truncate: true;
}

.time {
  font-size: 12px;
  color: #666;
  flex-shrink: 0;
}

.last-message {
  font-size: 13px;
  color: #666;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
