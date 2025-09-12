<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold text-gray-900">
          Verifikasi Passcode
        </DialogTitle>
      </DialogHeader>

      <div class="py-4">
        <!-- No Passcode Warning -->
        <div
          v-if="!hasPasscode"
          class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg"
        >
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg
                class="w-5 h-5 text-yellow-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">Passcode Belum Diatur</h3>
              <div class="mt-2 text-sm text-yellow-700">
                <p>
                  Anda harus mengatur passcode terlebih dahulu sebelum dapat melakukan
                  approval atau rejection dokumen.
                </p>
              </div>
              <div class="mt-4">
                <Button
                  @click="redirectToPasscode"
                  class="bg-yellow-600 hover:bg-yellow-700 text-white text-sm px-4 py-2"
                >
                  Atur Passcode Sekarang
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Passcode Input (only show if user has passcode) -->
        <div v-else>
          <div class="text-center mb-6">
            <h3 class="text-xl font-semibold text-gray-900">Enter Passcode</h3>
            <p class="text-sm text-gray-600 mt-1">Silahkan masukkan passcode anda</p>
          </div>

          <div class="flex items-center justify-center gap-3 mb-6">
            <input
              v-for="(digit, index) in digits"
              :key="index"
              ref="digitRefs"
              type="password"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="1"
              :value="digits[index]"
              @input="onDigitInput($event, index)"
              @keydown="onDigitKeydown($event, index)"
              class="w-12 h-12 rounded-xl border border-gray-300 text-center text-lg font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-green-400 disabled:bg-gray-100"
              :disabled="isVerifying"
            />
          </div>

          <div class="px-2">
            <div class="h-px w-full bg-gray-200" />
          </div>

          <p v-if="hasError" class="mt-3 text-center text-xs text-red-600">
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <DialogFooter class="flex justify-end gap-3">
        <Button
          variant="outline"
          @click="handleCancel"
          class="px-4 py-2"
          :disabled="isVerifying"
        >
          Batal
        </Button>
        <Button
          v-if="hasPasscode"
          @click="handleVerify"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white"
          :disabled="!isComplete || isVerifying"
        >
          <span v-if="isVerifying" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Memverifikasi...
          </span>
          <span v-else>Verifikasi</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
// Removed Input and Label since we now use custom PIN boxes
import { useApi } from "@/composables/useApi";

interface Props {
  isOpen: boolean;
  action: "verify" | "validate" | "approve" | "reject";
  actionData?: any;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  "update:open": [value: boolean];
  cancel: [];
  verified: [actionData: any];
}>();

// Check if user has passcode
const hasPasscode = ref(true); // Will be updated when component mounts

const { post, get } = useApi();

const passcode = ref("");
const hasError = ref(false);
const errorMessage = ref("");
const isVerifying = ref(false);

// Action text no longer displayed in the redesigned layout

// Check passcode status when dialog opens
watch(
  () => props.isOpen,
  async (newValue) => {
    if (newValue) {
      passcode.value = "";
      digits.value = ["", "", "", "", "", ""];
      hasError.value = false;
      errorMessage.value = "";
      isVerifying.value = false;

      // Check if user has passcode
      await checkPasscodeStatus();

      // focus first box when opened and has passcode
      if (hasPasscode.value) {
        requestAnimationFrame(() => moveFocus(0));
      }
    }
  }
);

// Check if user has passcode set
const checkPasscodeStatus = async () => {
  try {
    const response = await get("/api/auth/check-passcode-status");
    hasPasscode.value = response.has_passcode;
  } catch (error: any) {
    console.error("Error checking passcode status:", error);
    hasPasscode.value = false;
  }
};

// Redirect to passcode settings
const redirectToPasscode = () => {
  // Close the dialog first
  emit("update:open", false);

  // Redirect to passcode settings page
  const current =
    window.location.pathname + window.location.search + window.location.hash;
  const returnParam = encodeURIComponent(current);

  // Include action data in the redirect URL
  let redirectUrl = `/settings/security?return=${returnParam}`;
  if (props.actionData) {
    redirectUrl += `&action_data=${encodeURIComponent(JSON.stringify(props.actionData))}`;
  }

  window.location.href = redirectUrl;
};

const handleCancel = () => {
  passcode.value = "";
  digits.value = ["", "", "", "", "", ""];
  hasError.value = false;
  errorMessage.value = "";
  isVerifying.value = false;
  emit("cancel");
};

// PIN boxes state
const digits = ref<string[]>(["", "", "", "", "", ""]);
const digitRefs = ref<HTMLInputElement[]>();

const isComplete = computed(() => digits.value.every((d) => d !== ""));

const moveFocus = (index: number) => {
  if (!digitRefs.value) return;
  const el = (digitRefs.value[index] as unknown) as HTMLInputElement | undefined;
  el?.focus();
  el?.select();
};

const onDigitInput = (e: Event, index: number) => {
  const target = e.target as HTMLInputElement;
  const value = target.value.replace(/\D/g, "");
  digits.value[index] = value.slice(-1);
  if (value && index < digits.value.length - 1) {
    moveFocus(index + 1);
  }
};

const onDigitKeydown = (e: KeyboardEvent, index: number) => {
  const key = e.key;
  if (key === "Backspace") {
    if (digits.value[index] === "" && index > 0) {
      moveFocus(index - 1);
    } else {
      digits.value[index] = "";
    }
  } else if (key === "ArrowLeft" && index > 0) {
    e.preventDefault();
    moveFocus(index - 1);
  } else if (key === "ArrowRight" && index < digits.value.length - 1) {
    e.preventDefault();
    moveFocus(index + 1);
  } else if (key === "Enter") {
    handleVerify();
  }
};

const handleVerify = async () => {
  const combined = digits.value.join("");
  if (!combined.trim() || !isComplete.value) {
    hasError.value = true;
    errorMessage.value = "Passcode harus diisi";
    return;
  }

  isVerifying.value = true;
  hasError.value = false;
  errorMessage.value = "";

  try {
    // Verify passcode with backend
    const response = await post("/api/auth/verify-passcode", {
      passcode: combined,
    });

    if (response.success) {
      // Passcode is valid, emit verified event with action data
      emit("verified", props.actionData);
    } else {
      hasError.value = true;
      errorMessage.value = "Passcode yang Anda masukkan salah. Silakan coba lagi";
      // Clear the passcode form when verification fails
      digits.value = ["", "", "", "", "", ""];
      // Focus on the first input after clearing
      requestAnimationFrame(() => moveFocus(0));
    }
  } catch {
    hasError.value = true;
    errorMessage.value = "Passcode yang Anda masukkan salah. Silakan coba lagi";
    // Clear the passcode form when verification fails
    digits.value = ["", "", "", "", "", ""];
    // Focus on the first input after clearing
    requestAnimationFrame(() => moveFocus(0));
  } finally {
    isVerifying.value = false;
  }
};
</script>
