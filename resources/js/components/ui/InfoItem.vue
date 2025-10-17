<template>
  <div class="flex items-start gap-3">
    <component :is="iconComponent" class="w-5 h-5 text-gray-400 mt-0.5" />
    <div>
      <p class="text-sm font-medium text-gray-900">{{ label }}</p>
      <p
        :class="[
          'text-sm text-gray-600',
          { 'font-mono': mono, 'font-semibold': bold }
        ]"
      >
        {{ value }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import {
  Hash,
  FileText,
  Building2,
  ClipboardList,
  User,
  Calendar,
  CreditCard,
  DollarSign,
  Phone,
  MapPin,
} from "lucide-vue-next";

const props = defineProps<{
  icon: string;
  label: string;
  value: string;
  mono?: boolean;
  bold?: boolean;
}>();

const iconComponent = computed(() => {
  const iconMap: Record<string, any> = {
    hash: Hash,
    document: FileText,
    building: Building2,
    clipboard: ClipboardList,
    user: User,
    calendar: Calendar,
    "credit-card": CreditCard,
    currency: DollarSign,
    phone: Phone,
    map: MapPin,
  };
  return iconMap[props.icon] || FileText;
});
</script>
