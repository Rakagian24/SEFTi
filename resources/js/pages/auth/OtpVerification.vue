<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LoaderCircle, Smartphone, ArrowLeft } from 'lucide-vue-next';
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

onMounted(() => {
    startCountdown();
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

const handleOtpInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    let value = target.value.replace(/\D/g, '');

    if (value.length > 4) {
        value = value.slice(0, 4);
    }

    form.otp = value;

    // Auto-focus next input or submit when 4 digits entered
    if (value.length === 4) {
        setTimeout(() => {
            const submitBtn = document.getElementById('verify-btn');
            if (submitBtn && !form.processing) {
                submitBtn.focus();
            }
        }, 100);
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
            const otpInput = document.getElementById('otp-input');
            if (otpInput) {
                otpInput.focus();
            }
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
        },
    });
};

const goToLogin = () => {
    window.location.href = route('login');
};
</script>

<template>
    <Head title="Verifikasi OTP" />

    <div v-if="!props.asModal" class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="w-full max-w-6xl bg-[#333333] rounded-3xl shadow-xl overflow-hidden relative">
            <div class="flex h-[700px] relative">
                <!-- Form Panel (Right Side) -->
                <div class="w-1/2 flex items-center justify-center p-8 bg-[#DFECF2] relative">
                    <div class="w-full max-w-md">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <Smartphone class="w-8 h-8 text-blue-600" />
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Verifikasi No.Telepon Anda</h2>
                            <p class="text-gray-600 text-sm">
                                Kami telah mengirimkan kode verifikasi melalui Whatsapp ke nomor yang Anda masukkan. Silahkan masukkan kode tersebut pada kolom dibawah ini.
                            </p>
                        </div>

                        <!-- Status Message -->
                        <div v-if="($page.props.flash as any)?.status" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
                            {{ ($page.props.flash as any)?.status }}
                        </div>

                        <!-- Error Message -->
                        <div v-if="form.errors.otp" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                            {{ form.errors.otp }}
                        </div>

                        <!-- OTP Input Grid -->
                        <div class="mb-6">
                            <div class="flex justify-center space-x-4 mb-4">
                                <div
                                    v-for="(digit, index) in 4"
                                    :key="index"
                                    class="w-12 h-12 border-2 border-gray-300 rounded-lg flex items-center justify-center text-xl font-bold text-gray-700"
                                    :class="{
                                        'border-cyan-500 bg-cyan-50': form.otp.length > index,
                                        'border-red-500 bg-red-50': form.errors.otp && form.otp.length > index
                                    }"
                                >
                                    {{ form.otp[index] || '' }}
                                </div>
                            </div>

                            <!-- Hidden input for actual form submission -->
                            <Input
                                ref="otpInput"
                                id="otp-input"
                                type="text"
                                v-model="form.otp"
                                @input="handleOtpInput"
                                class="opacity-0 absolute -top-full"
                                placeholder="0000"
                                maxlength="4"
                                required
                                autocomplete="one-time-code"
                            />

                            <div class="text-center">
                                <p class="text-xs text-gray-500">
                                    Klik disini untuk memasukkan kode OTP
                                </p>
                                                                <button
                                    type="button"
                                    @click="($refs.otpInput as any)?.focus()"
                                    class="text-cyan-600 hover:text-cyan-700 text-sm font-medium"
                                >
                                    Masukkan kode OTP
                                </button>
                            </div>
                        </div>

                        <!-- Countdown Timer -->
                        <div class="text-center mb-6">
                            <p class="text-sm text-gray-600">
                                Code expires in:
                                <span class="font-mono font-bold" :class="countdown <= 60 ? 'text-red-600' : 'text-cyan-600'">
                                    {{ formatTime(countdown) }}
                                </span>
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <form @submit.prevent="verifyOtp" class="space-y-4">
                            <Button
                                id="verify-btn"
                                type="submit"
                                class="w-full h-12 bg-gray-800 hover:bg-gray-700 text-white font-medium rounded-full"
                                :disabled="form.processing || form.otp.length !== 4"
                            >
                                <LoaderCircle v-if="form.processing" class="w-5 h-5 animate-spin mr-2" />
                                Verifikasi
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="w-full h-12 border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-full"
                                :disabled="!canResend || form.processing"
                                @click="resendOtp"
                            >
                                <LoaderCircle v-if="form.processing" class="w-5 h-5 animate-spin mr-2" />
                                Kirim Lagi
                            </Button>
                        </form>

                        <!-- Back to Login -->
                        <div class="text-center mt-6">
                            <button
                                type="button"
                                @click="goToLogin"
                                class="text-sm text-gray-600 hover:text-gray-800 flex items-center justify-center mx-auto"
                            >
                                <ArrowLeft class="w-4 h-4 mr-1" />
                                Kembali ke Login
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Background Panel (Left Side) -->
                <div class="w-1/2 h-full bg-gradient-to-br from-gray-800 via-gray-900 to-black text-white relative">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-15">
                        <!-- Top left corner - concentric arcs -->
                        <div class="absolute top-8 left-8">
                            <div class="w-24 h-24 border-2 border-white rounded-full opacity-60"></div>
                            <div class="absolute top-2 left-2 w-20 h-20 border-2 border-white rounded-full opacity-40"></div>
                            <div class="absolute top-4 left-4 w-16 h-16 border-2 border-white rounded-full opacity-30"></div>
                            <div class="absolute top-6 left-6 w-12 h-12 border-2 border-white rounded-full opacity-20"></div>
                        </div>

                        <!-- Top right - geometric shapes -->
                        <div class="absolute top-16 right-16">
                            <div class="w-0 h-0 border-l-8 border-r-8 border-b-12 border-l-transparent border-r-transparent border-b-white transform rotate-45"></div>
                            <div class="absolute top-4 left-4 w-0 h-0 border-l-6 border-r-6 border-b-8 border-l-transparent border-r-transparent border-b-white transform -rotate-12"></div>
                            <div class="absolute -top-2 -right-2 w-0 h-0 border-l-4 border-r-4 border-b-6 border-l-transparent border-r-transparent border-b-white transform rotate-90"></div>
                        </div>

                        <!-- Center left - overlapping rectangles -->
                        <div class="absolute top-1/3 left-12">
                            <div class="w-16 h-24 border-2 border-white transform rotate-12 opacity-50"></div>
                            <div class="absolute top-2 left-2 w-12 h-20 border-2 border-white transform -rotate-6 opacity-40"></div>
                        </div>

                        <!-- Center right - triangular cluster -->
                        <div class="absolute top-1/2 right-20">
                            <div class="w-0 h-0 border-l-6 border-r-6 border-b-10 border-l-transparent border-r-transparent border-b-white opacity-60"></div>
                            <div class="absolute top-3 -left-2 w-0 h-0 border-l-4 border-r-4 border-b-6 border-l-transparent border-r-transparent border-b-white opacity-50"></div>
                            <div class="absolute top-3 left-2 w-0 h-0 border-l-4 border-r-4 border-b-6 border-l-transparent border-r-transparent border-b-white opacity-50"></div>
                            <div class="absolute top-6 left-0 w-0 h-0 border-l-3 border-r-3 border-b-4 border-l-transparent border-r-transparent border-b-white opacity-40"></div>
                        </div>

                        <!-- Bottom left - circular elements -->
                        <div class="absolute bottom-24 left-20">
                            <div class="w-8 h-8 bg-white rounded-full opacity-60"></div>
                            <div class="absolute -top-2 -right-2 w-4 h-4 bg-white rounded-full opacity-40"></div>
                            <div class="absolute top-6 left-6 w-3 h-3 bg-white rounded-full opacity-50"></div>
                        </div>

                        <!-- Bottom right - large circle with inner elements -->
                        <div class="absolute bottom-16 right-12">
                            <div class="w-20 h-20 border-2 border-white rounded-full opacity-40"></div>
                            <div class="absolute top-2 left-2 w-16 h-16 border border-white rounded-full opacity-30"></div>
                            <div class="absolute top-6 left-6 w-8 h-8 bg-white rounded-full opacity-60"></div>
                        </div>

                        <!-- Scattered small elements -->
                        <div class="absolute top-1/4 left-1/3 w-2 h-2 bg-white rounded-full opacity-50"></div>
                        <div class="absolute top-2/3 left-1/4 w-1.5 h-1.5 bg-white rounded-full opacity-60"></div>
                        <div class="absolute top-1/2 left-2/3 w-1 h-1 bg-white rounded-full opacity-70"></div>
                        <div class="absolute top-3/4 right-1/3 w-2.5 h-2.5 bg-white rounded-full opacity-40"></div>

                        <!-- Diagonal lines -->
                        <div class="absolute top-1/4 left-1/4 w-32 h-0.5 bg-white transform rotate-45 opacity-30"></div>
                        <div class="absolute bottom-1/3 right-1/4 w-24 h-0.5 bg-white transform -rotate-45 opacity-30"></div>
                        <div class="absolute top-1/3 right-1/2 w-20 h-0.5 bg-white transform rotate-12 opacity-25"></div>
                        <div class="absolute bottom-1/4 left-1/3 w-28 h-0.5 bg-white transform -rotate-12 opacity-25"></div>

                        <!-- Additional geometric elements -->
                        <div class="absolute top-2/3 left-1/2 w-6 h-6 border-2 border-white transform rotate-45 opacity-40"></div>
                        <div class="absolute top-1/6 right-1/2 w-4 h-8 border border-white transform rotate-30 opacity-35"></div>

                        <!-- X marks -->
                        <div class="absolute bottom-1/3 left-1/6">
                            <div class="w-6 h-0.5 bg-white transform rotate-45 opacity-40"></div>
                            <div class="w-6 h-0.5 bg-white transform -rotate-45 opacity-40"></div>
                        </div>
                        <div class="absolute top-1/5 left-2/3">
                            <div class="w-4 h-0.5 bg-white transform rotate-45 opacity-30"></div>
                            <div class="w-4 h-0.5 bg-white transform -rotate-45 opacity-30"></div>
                        </div>
                    </div>

                    <!-- Brand Logo -->
                    <div class="absolute top-8 left-8 z-10">
                        <div class="text-2xl font-normal">
                            <span class="text-white font-pacifico">SGT.</span>
                        </div>
                    </div>

                    <!-- Dynamic Content -->
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center z-10 p-8">
                            <h1 class="text-5xl font-bold mb-6">
                                Welcome to <span class="font-normal font-pacifico bg-gradient-to-b from-[#22C1C3] to-[#41499F] text-transparent bg-clip-text">SEFTi.</span>
                            </h1>
                            <p class="text-lg text-gray-300 mb-8 max-w-md mx-auto">
                                Transformasi Digital untuk Keuangan Anda.
                            </p>
                            <Button
                                @click="goToLogin"
                                variant="outline"
                                class="border-2 bg-[#333333] border-white text-white hover:bg-white hover:text-gray-900 px-8 py-3 text-base font-medium rounded-full"
                            >
                                Sign In
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Compact Layout -->
    <div v-else class="w-full">
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
                    <div class="flex justify-center space-x-3 mb-3">
                        <div
                            v-for="(digit, index) in 4"
                            :key="index"
                            class="w-10 h-10 border-2 border-gray-300 rounded-lg flex items-center justify-center text-lg font-bold text-gray-700"
                            :class="{
                                'border-cyan-500 bg-cyan-50': form.otp.length > index,
                                'border-red-500 bg-red-50': form.errors.otp && form.otp.length > index
                            }"
                        >
                            {{ form.otp[index] || '' }}
                        </div>
                    </div>

                    <Input
                        ref="otpInput"
                        id="otp-input"
                        type="text"
                        v-model="form.otp"
                        @input="handleOtpInput"
                        class="opacity-0 absolute -top-full"
                        placeholder="0000"
                        maxlength="4"
                        required
                        autocomplete="one-time-code"
                    />

                    <div class="text-center">
                        <button type="button" @click="($refs.otpInput as any)?.focus()" class="text-cyan-600 hover:text-cyan-700 text-xs font-medium">
                            Masukkan kode OTP
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
                    <Button id="verify-btn" type="submit" class="w-full h-10 bg-gray-800 hover:bg-gray-700 text-white font-medium rounded-full" :disabled="form.processing || form.otp.length !== 4">
                        <LoaderCircle v-if="form.processing" class="w-4 h-4 animate-spin mr-2" />
                        Verifikasi
                    </Button>
                    <Button type="button" variant="outline" class="w-full h-10 border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-full" :disabled="!canResend || form.processing" @click="resendOtp">
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
    transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Input focus states */
.focus\:border-cyan-500:focus {
    border-color: #06b6d4;
    box-shadow: none;
}

/* Smooth gradient background */
.bg-gradient-to-br {
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Z-index layering */
.z-0 {
    z-index: 0;
}

.z-10 {
    z-index: 10;
}

/* OTP Input Animation */
.otp-digit {
    transition: all 0.3s ease;
}

.otp-digit:hover {
    transform: scale(1.05);
}
</style>
