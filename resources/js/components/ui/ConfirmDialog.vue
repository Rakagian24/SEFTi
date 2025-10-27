<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px);"
      @click.self="$emit('cancel')"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all"
        @click.stop
      >
        <!-- Header dengan Icon -->
        <div class="pt-8 pb-4 px-6 text-center">
          <!-- <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center mb-4 animate-bounce-subtle">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div> -->

          <!-- Message -->
          <h3 class="text-xl font-semibold text-gray-900 mb-2">
            Konfirmasi Aksi
          </h3>
          <p class="text-gray-600 leading-relaxed">
            {{ message }}
          </p>
        </div>

        <!-- Actions -->
        <div class="px-6 pb-6 flex gap-3">
          <button
            type="button"
            @click="$emit('cancel')"
            class="flex-1 px-5 py-3 rounded-xl font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2"
          >
            Batal
          </button>
          <button
            type="button"
            @click="$emit('confirm')"
            class="flex-1 px-5 py-3 rounded-xl font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 active:scale-95 transition-all duration-150 shadow-lg shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            Ya, Lanjutkan
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{ show: boolean; message: string }>();
defineEmits(['confirm', 'cancel']);
</script>

<style scoped>
/* Modal Transition */
.modal-enter-active {
  transition: all 0.3s ease-out;
}

.modal-leave-active {
  transition: all 0.2s ease-in;
}

.modal-enter-from {
  opacity: 0;
}

.modal-enter-from > div {
  transform: scale(0.9) translateY(-20px);
  opacity: 0;
}

.modal-leave-to {
  opacity: 0;
}

.modal-leave-to > div {
  transform: scale(0.95);
  opacity: 0;
}

/* Subtle bounce animation */
@keyframes bounce-subtle {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

.animate-bounce-subtle {
  animation: bounce-subtle 2s ease-in-out infinite;
}
</style>
