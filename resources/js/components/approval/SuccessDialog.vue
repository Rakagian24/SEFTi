<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent
      class="sm:max-w-md bg-gradient-to-br from-blue-50 via-purple-50 to-indigo-50 border-0 shadow-2xl"
    >
      <!-- Animated Background Particles -->
      <div class="absolute inset-0 overflow-hidden rounded-lg">
        <div
          v-for="i in 6"
          :key="i"
          class="absolute w-2 h-2 bg-blue-300 rounded-full opacity-30 animate-float"
          :style="{
            left: `${Math.random() * 100}%`,
            top: `${Math.random() * 100}%`,
            animationDelay: `${Math.random() * 2}s`,
            animationDuration: `${3 + Math.random() * 2}s`,
          }"
        ></div>
      </div>

      <div class="flex flex-col items-center text-center py-8 relative z-10">
        <!-- Animated Success Icon -->
        <div class="relative mb-8">
          <!-- Outer ripple animation -->
          <div
            class="absolute inset-0 w-24 h-24 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full animate-ping opacity-20"
          ></div>
          <div
            class="absolute inset-1 w-22 h-22 bg-gradient-to-r from-blue-300 to-purple-400 rounded-full animate-pulse opacity-40"
          ></div>

          <!-- Main circle with gradient -->
          <div
            class="relative w-24 h-24 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg animate-scale-in"
          >
            <!-- Checkmark with draw animation -->
            <svg
              class="w-12 h-12 text-white animate-check-draw"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="3"
                d="M5 13l4 4L19 7"
                stroke-dasharray="20"
                stroke-dashoffset="20"
                class="animate-draw-check"
              />
            </svg>
          </div>

          <!-- Floating decorative elements -->
          <div
            class="absolute -top-3 -right-3 w-4 h-4 bg-blue-400 rounded-full opacity-70 animate-bounce-delayed-1"
          ></div>
          <div
            class="absolute -bottom-3 -left-3 w-3 h-3 bg-purple-400 rounded-full opacity-70 animate-bounce-delayed-2"
          ></div>
          <div
            class="absolute top-2 -left-4 w-3 h-3 bg-indigo-400 rounded-full opacity-70 animate-bounce-delayed-3 flex items-center justify-center"
          >
            <div class="w-1 h-1 bg-white rounded-full animate-pulse"></div>
          </div>
          <div
            class="absolute -top-2 -right-5 w-3 h-3 bg-blue-300 rounded-full opacity-70 animate-bounce-delayed-4 flex items-center justify-center"
          >
            <svg
              class="w-2 h-2 text-white animate-spin-slow"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
              />
            </svg>
          </div>

          <!-- Plus icon -->
          <div
            class="absolute -top-1 left-8 w-2 h-2 bg-white rounded-full opacity-80 animate-pulse-fast flex items-center justify-center"
          >
            <svg class="w-1 h-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
        </div>

        <!-- Animated Success Message -->
        <div class="space-y-4 animate-slide-up">
          <h3
            class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent animate-fade-in-up"
          >
            Thank you,
            <span
              class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
              >{{ userName }}!</span
            >
          </h3>

          <p
            class="text-base text-gray-700 leading-relaxed max-w-sm animate-fade-in-up-delayed"
          >
            {{ successMessage }}
          </p>

          <p
            class="text-base text-transparent bg-gradient-to-r from-[#22C1C3] to-[#41499F] bg-clip-text font-semibold animate-fade-in-up-delayed-2"
          >
            Have a great day :)
          </p>
        </div>
      </div>

      <DialogFooter class="flex justify-center pb-6 animate-fade-in-up-delayed-3">
        <Button
          @click="$emit('close')"
          class="px-8 py-3 bg-white border-2 border-gray-200 text-gray-700 font-medium rounded-full hover:bg-gray-50 hover:border-gray-300 hover:shadow-md transition-all duration-300 transform hover:scale-105 active:scale-95"
        >
          Kembali
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Dialog, DialogContent, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface Props {
  isOpen: boolean;
  action: "approve" | "reject";
  userName: string;
  documentType?: string;
}

const props = withDefaults(defineProps<Props>(), {
  documentType: "Dokumen",
});

const successMessage = computed(() => {
  if (props.action === "approve") {
    return `${props.documentType} telah berhasil disetujui. Terima kasih atas verifikasinya.`;
  } else {
    return `${props.documentType} telah berhasil ditolak. Terima kasih atas verifikasinya.`;
  }
});
</script>

<style scoped>
/* Floating animation for background particles */
@keyframes float {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
  }
  25% {
    transform: translateY(-10px) rotate(90deg);
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
  }
  75% {
    transform: translateY(-10px) rotate(270deg);
  }
}

/* Scale in animation for main icon */
@keyframes scale-in {
  0% {
    transform: scale(0) rotate(-180deg);
    opacity: 0;
  }
  50% {
    transform: scale(1.1) rotate(-90deg);
    opacity: 0.8;
  }
  100% {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

/* Checkmark draw animation */
@keyframes draw-check {
  0% {
    stroke-dashoffset: 20;
  }
  100% {
    stroke-dashoffset: 0;
  }
}

/* Slide up animation */
@keyframes slide-up {
  0% {
    transform: translateY(30px);
    opacity: 0;
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Fade in up animations with delays */
@keyframes fade-in-up {
  0% {
    transform: translateY(20px);
    opacity: 0;
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Bounce delayed animations for decorative elements */
@keyframes bounce-delayed {
  0%,
  20%,
  50%,
  80%,
  100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

/* Slow spin for star */
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Animation classes */
.animate-float {
  animation: float 4s ease-in-out infinite;
}

.animate-scale-in {
  animation: scale-in 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.animate-draw-check {
  animation: draw-check 0.8s ease-in-out 0.3s forwards;
}

.animate-slide-up {
  animation: slide-up 0.6s ease-out 0.4s both;
}

.animate-fade-in-up {
  animation: fade-in-up 0.6s ease-out 0.6s both;
}

.animate-fade-in-up-delayed {
  animation: fade-in-up 0.6s ease-out 0.8s both;
}

.animate-fade-in-up-delayed-2 {
  animation: fade-in-up 0.6s ease-out 1s both;
}

.animate-fade-in-up-delayed-3 {
  animation: fade-in-up 0.6s ease-out 1.2s both;
}

.animate-bounce-delayed-1 {
  animation: bounce-delayed 2s infinite 0.1s;
}

.animate-bounce-delayed-2 {
  animation: bounce-delayed 2s infinite 0.3s;
}

.animate-bounce-delayed-3 {
  animation: bounce-delayed 2s infinite 0.5s;
}

.animate-bounce-delayed-4 {
  animation: bounce-delayed 2s infinite 0.7s;
}

.animate-pulse-fast {
  animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}
</style>
