<script setup lang="ts">
// Tidak perlu import useSidebar karena akan dihandle oleh parent
import { defineProps } from 'vue';
const props = defineProps({
  state: { type: String, default: '' }
});
</script>

<template>
    <div class="flex items-center justify-center w-full logo-container">
        <span
          class="font-pacifico leading-tight text-white logo-text"
          :style="props.state === 'collapsed' ? ' font-size: 24px !important; text-align: center !important; width: 100% !important;' : ''"
        >SGT.</span>
    </div>
</template>

<style scoped>
.font-pacifico {
    font-family: 'Pacifico', cursive;
}

.logo-container {
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;
  justify-content: center !important;
  align-items: center !important;
  padding: 0 !important;
  margin: 0 !important;
  overflow: visible !important;
  display: flex !important;
  height: 48px !important;
}

.logo-text {
  font-family: 'Pacifico', cursive;
  font-size: 2.2rem !important;
  white-space: nowrap !important;
  overflow: visible !important;
  line-height: 3 !important;
  color: white;
}

/* Collapsed state: match sidebar's collapsed width/height */
:global(.group[data-state="collapsed"]) .logo-container {
  width: 100% !important;
  min-width: 80px !important;
  max-width: 80px !important;
  height: 80px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin: 0 !important;
  padding: 0 !important;
}

:global(.group[data-state="collapsed"]) .logo-text {
  font-size: 1.1rem !important;
  width: 100% !important;
  text-align: center !important;
  margin: 0 auto !important;
  line-height: 1.2 !important;
}
</style>
