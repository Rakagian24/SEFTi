<script setup lang="ts">
import { Bell, MessageCircle, Calendar } from 'lucide-vue-next';
import NotificationIcon from './NotificationIcon.vue';

interface Props {
  notifications: {
    calendar: number;
    messages: number;
    alerts: number;
  };
}

defineProps<Props>();

const emit = defineEmits<{
  calendarClick: [];
  messageClick: [];
  alertClick: [];
}>();
</script>

<template>
  <div class="flex items-center gap-2">
    <NotificationIcon
      :icon="Calendar"
      :count="notifications.calendar"
      variant="red"
      @click="emit('calendarClick')"
    />

    <NotificationIcon
      :icon="MessageCircle"
      :count="notifications.messages"
      variant="green"
      @click="emit('messageClick')"
    />

    <NotificationIcon
      :icon="Bell"
      :count="notifications.alerts"
      variant="red"
      @click="emit('alertClick')"
    />
  </div>
</template>
