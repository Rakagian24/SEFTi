<template>
  <div class="message-options">
    <button @click="toggleDropdown" class="options-btn" ref="optionsBtn">
      <span class="dots">⋮</span>
    </button>
    <div v-if="show" class="dropdown" ref="dropdown">
      <button @click="handleEdit" class="dropdown-item">
        <span class="icon">✏️</span>
        Edit
      </button>
      <button @click="handleDelete" class="dropdown-item delete">
        <span class="icon">🗑️</span>
        Hapus
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
  message: Object,
});

const emit = defineEmits(["edit", "delete"]);

const show = ref(false);
const optionsBtn = ref<HTMLElement | null>(null);
const dropdown = ref<HTMLElement | null>(null);

function toggleDropdown() {
  show.value = !show.value;
}

function handleEdit() {
  emit("edit", props.message);
  show.value = false;
}

function handleDelete() {
  if (confirm("Apakah Anda yakin ingin menghapus pesan ini?")) {
    emit("delete", props.message);
  }
  show.value = false;
}

function handleClickOutside(event: MouseEvent) {
  if (
    optionsBtn.value &&
    !optionsBtn.value.contains(event.target as Node) &&
    dropdown.value &&
    !dropdown.value.contains(event.target as Node)
  ) {
    show.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.message-options {
  position: relative;
  margin-left: 8px;
}

.options-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  color: #666;
  opacity: 0.7;
  transition: all 0.2s ease;
}

.options-btn:hover {
  background: rgba(0, 0, 0, 0.1);
  opacity: 1;
}

.dots {
  font-size: 16px;
  font-weight: bold;
  line-height: 1;
}

.dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
  min-width: 120px;
  overflow: hidden;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  color: #333;
  transition: background-color 0.2s ease;
}

.dropdown-item:hover {
  background: white;
}

.dropdown-item.delete {
  color: #dc3545;
}

.dropdown-item.delete:hover {
  background: #f8d7da;
}

.icon {
  font-size: 12px;
}
</style>
