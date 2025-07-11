<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { CheckCircle, XCircle, AlertCircle, Info, X } from 'lucide-vue-next';

interface Message {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title?: string;
  message: string;
  duration?: number;
}

interface Props {
  messages?: Message[];
  autoClose?: boolean;
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center';
}

const props = withDefaults(defineProps<Props>(), {
  messages: () => [],
  autoClose: true,
  position: 'top-right'
});

const emit = defineEmits<{
  close: [messageId: string];
  clear: [];
}>();

const localMessages = ref<Message[]>([]);

// Watch for new messages from props
watch(() => props.messages, (newMessages) => {
  if (newMessages.length > 0) {
    localMessages.value = [...localMessages.value, ...newMessages];
  }
}, { deep: true });

// Auto-close messages
watch(localMessages, (messages) => {
  messages.forEach((message) => {
    if (props.autoClose && message.duration !== 0) {
      setTimeout(() => {
        closeMessage(message.id);
      }, message.duration || 5000);
    }
  });
}, { deep: true });

function closeMessage(messageId: string) {
  localMessages.value = localMessages.value.filter(msg => msg.id !== messageId);
  emit('close', messageId);
}

function clearAll() {
  localMessages.value = [];
  emit('clear');
}

// Get icon based on message type
function getIcon(type: string) {
  switch (type) {
    case 'success':
      return CheckCircle;
    case 'error':
      return XCircle;
    case 'warning':
      return AlertCircle;
    case 'info':
      return Info;
    default:
      return Info;
  }
}

// Get color classes based on message type
function getColorClasses(type: string) {
  switch (type) {
    case 'success':
      return {
        bg: 'bg-green-50',
        border: 'border-green-200',
        text: 'text-green-800',
        icon: 'text-green-400',
        close: 'text-green-400 hover:text-green-600'
      };
    case 'error':
      return {
        bg: 'bg-red-50',
        border: 'border-red-200',
        text: 'text-red-800',
        icon: 'text-red-400',
        close: 'text-red-400 hover:text-red-600'
      };
    case 'warning':
      return {
        bg: 'bg-yellow-50',
        border: 'border-yellow-200',
        text: 'text-yellow-800',
        icon: 'text-yellow-400',
        close: 'text-yellow-400 hover:text-yellow-600'
      };
    case 'info':
      return {
        bg: 'bg-blue-50',
        border: 'border-blue-200',
        text: 'text-blue-800',
        icon: 'text-blue-400',
        close: 'text-blue-400 hover:text-blue-600'
      };
    default:
      return {
        bg: 'bg-gray-50',
        border: 'border-gray-200',
        text: 'text-gray-800',
        icon: 'text-gray-400',
        close: 'text-gray-400 hover:text-gray-600'
      };
  }
}

// Get position classes
function getPositionClasses() {
  switch (props.position) {
    case 'top-right':
      return 'top-4 right-4';
    case 'top-left':
      return 'top-4 left-4';
    case 'bottom-right':
      return 'bottom-4 right-4';
    case 'bottom-left':
      return 'bottom-4 left-4';
    case 'top-center':
      return 'top-4 left-1/2 transform -translate-x-1/2';
    case 'bottom-center':
      return 'bottom-4 left-1/2 transform -translate-x-1/2';
    default:
      return 'top-4 right-4';
  }
}

// Add message programmatically
function addMessage(message: Omit<Message, 'id'>) {
  const newMessage: Message = {
    ...message,
    id: Date.now().toString() + Math.random().toString(36).substr(2, 9)
  };
  localMessages.value.push(newMessage);
}

// Expose methods for parent components
defineExpose({
  addMessage,
  clearAll
});
</script>

<template>
  <div
    v-if="localMessages.length > 0"
    :class="[
      'fixed z-50 flex flex-col gap-3 max-w-sm w-full',
      getPositionClasses()
    ]"
  >
    <!-- Clear All Button -->
    <div v-if="localMessages.length > 1" class="flex justify-end">
      <button
        @click="clearAll"
        class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors duration-200"
      >
        Clear All
      </button>
    </div>

    <!-- Messages -->
    <TransitionGroup
      name="message"
      tag="div"
      class="flex flex-col gap-3"
    >
      <div
        v-for="message in localMessages"
        :key="message.id"
        :class="[
          'relative p-4 rounded-lg border shadow-lg max-w-sm w-full transform transition-all duration-300 ease-in-out',
          getColorClasses(message.type).bg,
          getColorClasses(message.type).border
        ]"
      >
        <!-- Icon and Content -->
        <div class="flex items-start gap-3">
          <component
            :is="getIcon(message.type)"
            :class="[
              'w-5 h-5 mt-0.5 flex-shrink-0',
              getColorClasses(message.type).icon
            ]"
          />

          <div class="flex-1 min-w-0">
            <h4
              v-if="message.title"
              :class="[
                'text-sm font-medium mb-1',
                getColorClasses(message.type).text
              ]"
            >
              {{ message.title }}
            </h4>
            <p
              :class="[
                'text-sm',
                getColorClasses(message.type).text
              ]"
            >
              {{ message.message }}
            </p>
          </div>

          <!-- Close Button -->
          <button
            @click="closeMessage(message.id)"
            :class="[
              'ml-2 p-1 rounded-md transition-colors duration-200',
              getColorClasses(message.type).close
            ]"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.message-enter-active,
.message-leave-active {
  transition: all 0.3s ease;
}

.message-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.message-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.message-move {
  transition: transform 0.3s ease;
}
</style>
