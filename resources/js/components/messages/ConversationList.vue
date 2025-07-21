<template>
  <div class="conversation-list">
    <div class="header">
      <div class="pb-4">
        <h2 class="text-xl font-semibold text-gray-900">Messages</h2>
        <div class="w-8 h-0.5 bg-[rgba(51,51,51,0.5)] mt-2"></div>
      </div>
      <div class="header-controls">
        <button class="new-chat-btn" @click="$emit('newChat')">
          <span class="plus-icon">+</span>
          New Chat
        </button>
        <div class="sort-container">
          <span class="sort-icon">⚙</span>
          <span class="sort-label">Sort:</span>
          <select v-model="sort" @change="() => fetchConversations()" class="sort-select">
            <option value="latest">a-z</option>
            <option value="oldest">z-a</option>
          </select>
        </div>
        <div class="search-container">
          <input
            type="text"
            placeholder="Search..."
            v-model="search"
            @input="() => fetchConversations()"
            class="search-input"
          />
          <span class="search-icon">🔍</span>
        </div>
      </div>
    </div>

    <div class="conversation-items">
      <div v-for="conversation in conversations" :key="conversation.id">
        <ConversationItem
          :conversation="conversation"
          :isSelected="selectedConversation?.id === conversation.id"
          @click="$emit('selectConversation', conversation)"
        />
      </div>
    </div>

    <div
      class="pagination"
      v-if="pagination.current_page > 1 || pagination.next_page_url"
    >
      <button
        :disabled="!pagination.prev_page_url"
        @click="fetchConversations(pagination.current_page - 1)"
        class="pagination-btn"
      >
        Prev
      </button>
      <span class="page-info">Halaman {{ pagination.current_page }}</span>
      <button
        :disabled="!pagination.next_page_url"
        @click="fetchConversations(pagination.current_page + 1)"
        class="pagination-btn"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import ConversationItem from "./ConversationItem.vue";

interface Conversation {
  id: number | string;
  [key: string]: any;
}
const search = ref("");
const sort = ref("latest");
const conversations = ref<Conversation[]>([]);
const pagination = ref({ current_page: 1, prev_page_url: null, next_page_url: null });

function fetchConversations(page = 1) {
  axios
    .get("/api/settings/message", {
      params: { search: search.value, sort: sort.value, page },
    })
    .then((res) => {
      conversations.value = res.data.data;
      pagination.value = {
        current_page: res.data.current_page,
        prev_page_url: res.data.prev_page_url,
        next_page_url: res.data.next_page_url,
      };
    });
}

onMounted(() => fetchConversations());

defineExpose({ fetchConversations });
defineProps<{ selectedConversation?: Conversation }>();
</script>

<style scoped>
.conversation-list {
  width: 350px;
  background: white;
  border-right: 1px solid #e9ecef;
  display: flex;
  flex-direction: column;
  height: 100vh;
}

.header {
  padding: 20px;
  border-bottom: 1px solid #e9ecef;
  background: white;
}

.title {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 20px 0;
  color: #333;
}

.header-controls {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.new-chat-btn {
  background: #333;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  align-self: flex-start;
}

.new-chat-btn:hover {
  background: #555;
}

.plus-icon {
  font-size: 16px;
  font-weight: bold;
}

.sort-container {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.sort-icon {
  font-size: 16px;
}

.sort-label {
  color: #666;
}

.sort-select {
  border: none;
  background: none;
  font-size: 14px;
  color: #333;
  cursor: pointer;
}

.search-container {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 8px 12px;
  padding-right: 35px;
  border: 1px solid #ddd;
  border-radius: 20px;
  font-size: 14px;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: #007bff;
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
  font-size: 14px;
}

.conversation-items {
  flex: 1;
  overflow-y: auto;
}

.pagination {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #e9ecef;
  background: white;
}

.pagination-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.pagination-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.page-info {
  font-size: 12px;
  color: #666;
}
</style>
