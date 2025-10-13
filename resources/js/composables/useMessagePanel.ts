import { ref } from 'vue';

interface Message {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title?: string;
  message: string;
  duration?: number;
}

const messages = ref<Message[]>([]);

export function useMessagePanel() {
  const addMessage = (message: Omit<Message, 'id'>) => {
    // Hapus pesan dengan tipe yang sama sebelum menambah yang baru
    messages.value = messages.value.filter(msg => msg.type !== message.type);
    const newMessage: Message = {
      ...message,
      id: Date.now().toString() + Math.random().toString(36).substr(2, 9)
    };
    messages.value.push(newMessage);
  };

  const addSuccess = (message: string, title?: string, duration?: number) => {
    addMessage({
      type: 'success',
      title,
      message,
      duration
    });
  };

  const addError = (message: string, title?: string, duration?: number) => {
    addMessage({
      type: 'error',
      title,
      message,
      duration
    });
  };

  const addWarning = (message: string, title?: string, duration?: number) => {
    addMessage({
      type: 'warning',
      title,
      message,
      duration
    });
  };

  const addInfo = (message: string, title?: string, duration?: number) => {
    addMessage({
      type: 'info',
      title,
      message,
      duration
    });
  };

  const removeMessage = (id: string) => {
    messages.value = messages.value.filter(msg => msg.id !== id);
  };

  const clearAll = () => {
    messages.value = [];
  };

  return {
    messages,
    addMessage,
    addSuccess,
    addError,
    addWarning,
    addInfo,
    removeMessage,
    clearAll
  };
}
