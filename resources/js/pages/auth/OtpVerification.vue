<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { LoaderCircle, Smartphone } from 'lucide-vue-next';
import { useAlertDialog } from '@/composables/useAlertDialog';

const props = defineProps<{
    phone: string;
    asModal?: boolean;
}>();

const form = useForm({
    phone: props.phone,
    otp: '',
});

const countdown = ref(300); // 5 minutes in seconds
const canResend = ref(false);
const resendCount = ref(0);
const { showWarning } = useAlertDialog();
let countdownInterval: number | undefined;

// Refs for OTP inputs
const otpInputs = ref<HTMLInputElement[]>([]);

const setOtpRef = (el: any, index: number) => {
    if (el) {
        otpInputs.value[index] = el as HTMLInputElement;
    }
};

const focusInput = (index: number) => {
    nextTick(() => {
        if (otpInputs.value[index]) {
            otpInputs.value[index].focus();
        }
    });
};

onMounted(() => {
    startCountdown();
    // Focus first input on mount
    focusInput(0);
    if (props.asModal) {
        // Trigger initial OTP send when shown as modal
        resendOtp();
    }
});

onBeforeUnmount(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

const startCountdown = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    countdownInterval = window.setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(countdownInterval);
            canResend.value = true;
        }
    }, 1000);
};

const formatTime = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const handleSingleInput = (event: Event, index: number) => {
    const target = event.target as HTMLInputElement;
    const value = target.value.replace(/\D/g, '');

    if (value.length > 1) {
        // Handle paste of multiple digits
        handlePaste(value);
        return;
    }

    // Update single digit
    const otpArray = form.otp.split('');
    otpArray[index] = value;
    form.otp = otpArray.join('').slice(0, 4);

    // Move to next input if digit entered
    if (value && index < 3) {
        focusInput(index + 1);
    }

    // Auto-submit when all 4 digits entered
    if (form.otp.length === 4 && !form.processing) {
        setTimeout(() => verifyOtp(), 300);
    }
};

const handlePaste = (pastedValue: string) => {
    const digits = pastedValue.replace(/\D/g, '').slice(0, 4);
    form.otp = digits;

    // Fill individual inputs visually
    digits.split('').forEach((digit, i) => {
        if (otpInputs.value[i]) {
            otpInputs.value[i].value = digit;
        }
    });

    // Focus next empty or last input
    const nextIndex = Math.min(digits.length, 3);
    focusInput(nextIndex);

    // Auto-submit if complete
    if (digits.length === 4 && !form.processing) {
        setTimeout(() => verifyOtp(), 300);
    }
};

const handleBackspace = (event: KeyboardEvent, index: number) => {
    const target = event.target as HTMLInputElement;

    if (!target.value && index > 0) {
        // Move to previous input if current is empty
        event.preventDefault();
        const otpArray = form.otp.split('');
        otpArray[index - 1] = '';
        form.otp = otpArray.join('');
        focusInput(index - 1);
    } else {
        // Clear current digit
        const otpArray = form.otp.split('');
        otpArray[index] = '';
        form.otp = otpArray.join('');
    }
};

const handleKeyDown = (event: KeyboardEvent, index: number) => {
    // Allow arrow key navigation
    if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        focusInput(index - 1);
    } else if (event.key === 'ArrowRight' && index < 3) {
        event.preventDefault();
        focusInput(index + 1);
    }
    // Allow backspace for deletion (handled in handleBackspace)
    else if (event.key === 'Backspace') {
        return;
    }
    // Allow only numbers
    else if (!/^\d$/.test(event.key) && !['Tab', 'Delete'].includes(event.key)) {
        event.preventDefault();
    }
};

const verifyOtp = () => {
    if (form.otp.length !== 4) return;

    form.post(route('otp.verify.attempt'), {
        onSuccess: () => {
            // Redirect will be handled by controller
        },
        onError: () => {
            // Reset form on error
            form.otp = '';
            otpInputs.value.forEach(input => {
                if (input) input.value = '';
            });
            focusInput(0);
        }
    });
};

const resendOtp = () => {
    if (resendCount.value >= 3) {
        showWarning('Terlalu banyak permintaan. Silakan tunggu 15 menit.', 'Peringatan');
        return;
    }

    form.post(route('otp.resend'), {
        onSuccess: () => {
            resendCount.value++;
            canResend.value = false;
            countdown.value = 300;
            startCountdown();
            form.otp = '';
            otpInputs.value.forEach(input => {
                if (input) input.value = '';
            });
            focusInput(0);
        },
    });
};
</script>

<template>
    <Head title="Verifikasi OTP" />

    <!-- Modal Layout Only -->
    <div class="w-full">
        <div class="w-full bg-[#DFECF2] rounded-2xl p-6">
            <div class="w-full max-w-md mx-auto">
                <div class="text-center mb-6">
                    <div class="mx-auto w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                        <Smartphone class="w-7 h-7 text-blue-600" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Verifikasi No. Telepon</h2>
                    <p class="text-gray-600 text-xs">Kami telah mengirimkan kode via WhatsApp. Masukkan kode 4 digit.</p>
                </div>

                <div v-if="($page.props.flash as any)?.status" class="mb-3 p-2 bg-green-100 border border-green-400 text-green-700 rounded-lg text-xs">
                    {{ ($page.props.flash as any)?.status }}
                </div>
                <div v-if="form.errors.otp" class="mb-3 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-xs">
                    {{ form.errors.otp }}
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-center gap-3 mb-3">
                        <input
                            v-for="index in 4"
                            :key="index - 1"
                            :ref="(el) => setOtpRef(el, index - 1)"
                            type="text"
                            maxlength="1"
                            :value="form.otp[index - 1] || ''"
                            @input="handleSingleInput($event, index - 1)"
                            @keydown.backspace="handleBackspace($event, index - 1)"
                            @keydown="handleKeyDown($event, index - 1)"
                            class="w-12 h-12 rounded-xl border-2 text-center text-lg font-medium text-gray-800 outline-none transition-all"
                            :class="{
                                'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200': !form.errors.otp,
                                'border-red-500 bg-red-50 focus:border-red-600 focus:ring-2 focus:ring-red-200': form.errors.otp
                            }"
                            inputmode="numeric"
                            pattern="[0-9]"
                            autocomplete="off"
                        />
                    </div>

                    <div class="text-center">
                        <button type="button" @click="focusInput(0)" class="text-cyan-600 hover:text-cyan-700 text-xs font-medium">
                            Klik untuk fokus input
                        </button>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <p class="text-xs text-gray-600">
                        Kode kedaluwarsa dalam
                        <span class="font-mono font-bold" :class="countdown <= 60 ? 'text-red-600' : 'text-cyan-600'">
                            {{ formatTime(countdown) }}
                        </span>
                    </p>
                </div>

                <form @submit.prevent="verifyOtp" class="space-y-3">
                    <Button
                        id="verify-btn"
                        type="submit"
                        class="w-full h-10 bg-gray-800 hover:bg-gray-700 text-white font-medium rounded-full"
                        :disabled="form.processing || form.otp.length !== 4"
                    >
                        <LoaderCircle v-if="form.processing" class="w-4 h-4 animate-spin mr-2" />
                        Verifikasi
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full h-10 border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-full"
                        :disabled="!canResend || form.processing"
                        @click="resendOtp"
                    >
                        <LoaderCircle v-if="form.processing" class="w-4 h-4 animate-spin mr-2" />
                        Kirim Lagi
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom transitions */
.transition-all {
    transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Input focus states - enhanced for better visibility */
input:focus {
    transform: scale(1.05);
}

/* Smooth gradient background */
.bg-gradient-to-br {
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Remove number input spinners */
input[type="text"]::-webkit-inner-spin-button,
input[type="text"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="text"] {
    appearance: textfield;
    -moz-appearance: textfield;
}
</style>
