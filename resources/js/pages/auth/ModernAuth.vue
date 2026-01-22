<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useApi } from '@/composables/useApi';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Building, Eye, EyeOff, LoaderCircle, Lock, Mail, Phone, Shield, User } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OtpVerification from './OtpVerification.vue';

const { refreshCsrfToken } = useApi();

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    departments: Array<{ id: number; name: string; status: string }>;
    roles: Array<{
        id: number;
        name: string;
        description: string;
        permissions: string[];
        status: string;
    }>;
    otpPhone?: string | null;
}>();

const isLogin = ref(true);
const showPassword = ref(false);
const showOtpModal = ref(false);
const modalOtpPhone = ref<string>('');

// Login form
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

// Register form
const registerForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role_id: '',
    department_ids: [] as string[],
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onSuccess: async () => {
            await refreshCsrfToken();
            await router.reload({ only: ['auth'] });
        },
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    const uniqueDeptIds = Array.from(new Set(selectedDepartments.value.map(String)));
    registerForm.department_ids = uniqueDeptIds;
    if (registerForm.department_ids.length === 0) {
        registerForm.setError('department_ids', 'Pilih minimal satu department');
        return;
    }
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

const toggleForm = () => {
    isLogin.value = !isLogin.value;
};

// Desktop dropdown refs and states
const roleDropdownOpen = ref(false);
const departmentDropdownOpen = ref(false);
const roleDropdownRef = ref<HTMLElement | null>(null);
const departmentDropdownRef = ref<HTMLElement | null>(null);

const roleDropdownStyle = ref<Record<string, string>>({});
const departmentDropdownStyle = ref<Record<string, string>>({});

function updateRoleDropdownPosition() {
    if (!roleDropdownRef.value) return;
    const rect = roleDropdownRef.value.getBoundingClientRect();
    roleDropdownStyle.value = {
        position: 'absolute',
        top: `${rect.bottom + window.scrollY}px`,
        left: `${rect.left + window.scrollX}px`,
        width: `${rect.width}px`,
        zIndex: '9999',
    };
}

function updateDepartmentDropdownPosition() {
    if (!departmentDropdownRef.value) return;
    const rect = departmentDropdownRef.value.getBoundingClientRect();
    departmentDropdownStyle.value = {
        position: 'absolute',
        top: `${rect.bottom + window.scrollY}px`,
        left: `${rect.left + window.scrollX}px`,
        width: `${rect.width}px`,
        zIndex: '9999',
    };
}

watch(roleDropdownOpen, async (val) => {
    if (val) {
        await nextTick();
        updateRoleDropdownPosition();
    }
});

watch(departmentDropdownOpen, async (val) => {
    if (val) {
        await nextTick();
        updateDepartmentDropdownPosition();
    }
});

window.addEventListener(
    'scroll',
    () => {
        if (roleDropdownOpen.value) updateRoleDropdownPosition();
        if (departmentDropdownOpen.value) updateDepartmentDropdownPosition();
    },
    true,
);

function handleClickOutside(event: MouseEvent) {
    if (roleDropdownRef.value && roleDropdownRef.value.contains(event.target as Node)) return;
    roleDropdownOpen.value = false;
    if (departmentDropdownRef.value && departmentDropdownRef.value.contains(event.target as Node)) return;
    departmentDropdownOpen.value = false;
}

const registerTaglines = [
    'Raih Kendali Atas Keuangan Anda',
    'Akses Mudah, Kendali Penuh',
    'Satu Sistem, Segala Solusi Keuangan.',
    'Transformasi Digital untuk Keuangan Anda.',
];
const taglineIndex = ref(0);
let taglineInterval: number | undefined;
const showTagline = ref(true);

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    taglineInterval = window.setInterval(() => {
        showTagline.value = false;
        setTimeout(() => {
            taglineIndex.value = (taglineIndex.value + 1) % registerTaglines.length;
            showTagline.value = true;
        }, 400);
    }, 2500);
    if (props.otpPhone) {
        showOtpModal.value = true;
        modalOtpPhone.value = props.otpPhone as string;
    }
});

watch(
    () => props.otpPhone,
    (val) => {
        if (val) {
            showOtpModal.value = true;
            modalOtpPhone.value = val as string;
        }
    },
);

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    if (taglineInterval) clearInterval(taglineInterval);
});

const selectedDepartments = ref<string[]>([]);

const selectedDepartmentNames = computed(() => {
    if (selectedDepartments.value.length === 0) return '';
    if (selectedDepartments.value.length === 1) {
        const dept = props.departments.find((d) => d.id.toString() === selectedDepartments.value[0]);
        return dept ? dept.name : '';
    }
    return `${selectedDepartments.value.length} departments selected`;
});

function handlePhoneInput(event: Event) {
    const input = event.target as HTMLInputElement;
    const digitsOnly = input.value.replace(/\D+/g, '');
    if (input.value !== digitsOnly) {
        input.value = digitsOnly;
    }
    registerForm.phone = digitsOnly;
}
</script>

<template>
    <Head title="Authentication" />
    <div class="min-h-screen w-full bg-[#333333]">
        <!-- MOBILE LAYOUT -->
        <div class="flex min-h-screen flex-col md:hidden">
            <!-- Mobile Header -->
            <div class="bg-gradient-to-br from-gray-800 via-gray-900 to-black p-6 pb-8 text-white">
                <div class="mb-8 text-2xl font-normal">
                    <span class="font-pacifico text-white">SGT.</span>
                </div>
                <h1 class="mb-2 text-3xl font-bold">
                    <span v-if="isLogin">Sign In</span>
                    <span v-else>Sign Up</span>
                </h1>
                <p class="text-sm text-gray-300">
                    <span v-if="isLogin">Masuk ke akun Anda</span>
                    <span v-else>Buat akun baru</span>
                </p>
            </div>

            <!-- Mobile Form Container -->
            <div class="relative flex-1 overflow-hidden bg-[#DFECF2]">
                <div class="h-full overflow-y-auto">
                    <div
                        class="flex transition-transform duration-700 ease-in-out"
                        :style="{ transform: isLogin ? 'translateX(0)' : 'translateX(-100%)' }"
                    >
                        <div class="w-full flex-shrink-0 p-6">
                            <div class="mx-auto w-full max-w-md">
                                <form @submit.prevent="submitLogin" class="space-y-6">
                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Mail
                                                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <Input
                                                id="mobile-login-email"
                                                type="email"
                                                v-model="loginForm.email"
                                                class="peer h-12 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                                placeholder=""
                                                required
                                            />
                                            <label
                                                for="mobile-login-email"
                                                class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                            >
                                                Email<span class="text-red-500">*</span>
                                            </label>
                                        </div>
                                        <div v-if="loginForm.errors.email" class="text-xs text-red-500">
                                            {{ loginForm.errors.email }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Lock
                                                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <Input
                                                id="mobile-login-password"
                                                :type="showPassword ? 'text' : 'password'"
                                                v-model="loginForm.password"
                                                class="peer h-12 border-0 border-b-2 border-gray-700 bg-transparent pr-10 pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                                placeholder=""
                                                required
                                            />
                                            <button
                                                type="button"
                                                @click="showPassword = !showPassword"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                            >
                                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                                <EyeOff v-else class="h-5 w-5" />
                                            </button>
                                            <label
                                                for="mobile-login-password"
                                                class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                            >
                                                Password<span class="text-red-500">*</span>
                                            </label>
                                        </div>
                                        <div v-if="loginForm.errors.password" class="text-xs text-red-500">
                                            {{ loginForm.errors.password }}
                                        </div>
                                    </div>

                                    <!-- <div class="text-right">
              <button
                v-if="canResetPassword"
                type="button"
                class="text-sm text-gray-900 hover:text-black"
              >
                Lupa kata sandi?
              </button>
            </div> -->

                                    <Button
                                        type="submit"
                                        class="h-12 w-full rounded-full bg-gray-800 font-medium text-white hover:bg-gray-700"
                                        :disabled="loginForm.processing"
                                    >
                                        <LoaderCircle v-if="loginForm.processing" class="mr-2 h-5 w-5 animate-spin" />
                                        Sign In
                                    </Button>

                                    <div class="pt-4 text-center">
                                        <p class="text-sm text-gray-600">
                                            Belum punya akun?
                                            <button type="button" @click="toggleForm" class="font-semibold text-cyan-600 hover:text-cyan-700">
                                                Sign Up
                                            </button>
                                        </p>
                                    </div>
                                </form>
                                <!-- Login form content -->
                            </div>
                        </div>
                        <!-- Login Form Mobile -->
                        <!-- <div v-show="isLogin" class="w-full max-w-md mx-auto">
          <div
            v-if="status"
            class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm"
          >
            {{ status }}
          </div>


        </div> -->

                        <!-- Register Form Mobile -->
                        <div class="w-full flex-shrink-0 p-6">
                            <div class="mx-auto w-full max-w-md pb-8">
                                <!-- Register form content -->
                                <form @submit.prevent="submitRegister" class="space-y-4">
                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <User
                                                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <Input
                                                id="mobile-register-name"
                                                type="text"
                                                v-model="registerForm.name"
                                                class="peer h-12 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                                placeholder=""
                                                required
                                            />
                                            <label
                                                for="mobile-register-name"
                                                class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                            >
                                                Nama Lengkap<span class="text-red-500">*</span>
                                            </label>
                                        </div>
                                        <div v-if="registerForm.errors.name" class="text-xs text-red-500">
                                            {{ registerForm.errors.name }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Mail
                                                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <Input
                                                id="mobile-register-email"
                                                type="email"
                                                v-model="registerForm.email"
                                                class="peer h-12 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                                placeholder=""
                                                required
                                            />
                                            <label
                                                for="mobile-register-email"
                                                class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                            >
                                                Email<span class="text-red-500">*</span>
                                            </label>
                                        </div>
                                        <div v-if="registerForm.errors.email" class="text-xs text-red-500">
                                            {{ registerForm.errors.email }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Lock
                                                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <Input
                                                id="mobile-register-password"
                                                :type="showPassword ? 'text' : 'password'"
                                                v-model="registerForm.password"
                                                class="peer h-12 border-0 border-b-2 border-gray-700 bg-transparent pr-10 pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                                placeholder=""
                                                required
                                            />
                                            <button
                                                type="button"
                                                @click="showPassword = !showPassword"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                            >
                                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                                <EyeOff v-else class="h-5 w-5" />
                                            </button>
                                            <label
                                                for="mobile-register-password"
                                                class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                            >
                                                Password<span class="text-red-500">*</span>
                                            </label>
                                        </div>
                                        <div v-if="registerForm.errors.password" class="text-xs text-red-500">
                                            {{ registerForm.errors.password }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Phone
                                                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <Input
                                                id="mobile-register-phone"
                                                type="tel"
                                                v-model="registerForm.phone"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                @input="handlePhoneInput"
                                                class="peer h-12 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                                placeholder=""
                                                required
                                            />
                                            <label
                                                for="mobile-register-phone"
                                                class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                            >
                                                Phone<span class="text-red-500">*</span>
                                            </label>
                                        </div>
                                        <div v-if="registerForm.errors.phone" class="text-xs text-red-500">
                                            {{ registerForm.errors.phone }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Shield
                                                class="pointer-events-none absolute top-1/2 left-3 z-10 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <div
                                                class="peer relative flex h-12 w-full cursor-pointer appearance-none items-center border-0 border-b-2 border-gray-700 bg-transparent pr-8 pl-10 text-gray-700 placeholder-transparent focus-within:border-cyan-500 focus-within:ring-0"
                                                @click="roleDropdownOpen = !roleDropdownOpen"
                                                tabindex="0"
                                            >
                                                <span :class="[registerForm.role_id ? 'text-gray-700' : 'text-gray-400']">
                                                    {{ props.roles.find((r) => r.id.toString() === registerForm.role_id)?.name || '' }}
                                                </span>
                                                <svg
                                                    class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                                <label
                                                    :class="[
                                                        'pointer-events-none absolute left-10 transform duration-200',
                                                        !registerForm.role_id
                                                            ? 'top-1/2 -translate-y-1/2 text-base text-gray-400'
                                                            : 'top-0 -translate-y-0 text-xs text-gray-700',
                                                    ]"
                                                >
                                                    Role<span class="text-red-500">*</span>
                                                </label>
                                            </div>
                                            <div
                                                v-if="roleDropdownOpen"
                                                class="absolute top-full left-0 z-[9999] mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                                            >
                                                <div
                                                    v-for="role in props.roles"
                                                    :key="role.id"
                                                    @click="
                                                        registerForm.role_id = role.id.toString();
                                                        roleDropdownOpen = false;
                                                    "
                                                    class="cursor-pointer px-4 py-3 text-gray-700 first:rounded-t-xl last:rounded-b-xl hover:bg-cyan-50 hover:text-cyan-700"
                                                    :class="{ 'bg-cyan-50 font-semibold text-cyan-700': registerForm.role_id === role.id.toString() }"
                                                >
                                                    {{ role.name }}
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="registerForm.errors.role_id" class="text-xs text-red-500">
                                            {{ registerForm.errors.role_id }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="relative mt-6">
                                            <Building
                                                class="pointer-events-none absolute top-1/2 left-3 z-10 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                            />
                                            <div
                                                class="peer relative flex h-12 w-full cursor-pointer appearance-none items-center border-0 border-b-2 border-gray-700 bg-transparent pr-8 pl-10 text-gray-700 placeholder-transparent focus-within:border-cyan-500 focus-within:ring-0"
                                                @click="departmentDropdownOpen = !departmentDropdownOpen"
                                                tabindex="0"
                                            >
                                                <span :class="[selectedDepartments.length > 0 ? 'text-gray-700' : 'text-gray-400']">
                                                    {{ selectedDepartmentNames || '' }}
                                                </span>
                                                <svg
                                                    class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                                <label
                                                    :class="[
                                                        'pointer-events-none absolute left-10 transform duration-200',
                                                        selectedDepartments.length === 0
                                                            ? 'top-1/2 -translate-y-1/2 text-base text-gray-400'
                                                            : 'top-0 -translate-y-0 text-xs text-gray-700',
                                                    ]"
                                                >
                                                    Department<span class="text-red-500">*</span>
                                                </label>
                                            </div>
                                            <div
                                                v-if="departmentDropdownOpen"
                                                class="absolute top-full left-0 z-[9999] mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                                            >
                                                <div
                                                    v-for="department in props.departments"
                                                    :key="department.id"
                                                    @click.stop
                                                    class="flex cursor-pointer items-center gap-3 px-4 py-3 text-gray-700 first:rounded-t-xl last:rounded-b-xl hover:bg-cyan-50"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :id="`mobile-dept-${department.id}`"
                                                        :value="department.id.toString()"
                                                        v-model="selectedDepartments"
                                                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-cyan-600 focus:ring-2 focus:ring-cyan-500"
                                                    />
                                                    <label :for="`mobile-dept-${department.id}`" class="flex-1 cursor-pointer">
                                                        {{ department.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="registerForm.errors.department_ids" class="text-xs text-red-500">
                                            {{ registerForm.errors.department_ids }}
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        class="mt-6 h-12 w-full rounded-full bg-gray-800 font-medium text-white hover:bg-gray-700"
                                        :disabled="registerForm.processing"
                                    >
                                        <LoaderCircle v-if="registerForm.processing" class="mr-2 h-5 w-5 animate-spin" />
                                        Sign Up
                                    </Button>

                                    <div class="pt-4 text-center">
                                        <p class="text-sm text-gray-600">
                                            Sudah punya akun?
                                            <button type="button" @click="toggleForm" class="font-semibold text-cyan-600 hover:text-cyan-700">
                                                Sign In
                                            </button>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Register Form Mobile -->
                        <!-- <div v-show="!isLogin" class="w-full max-w-md mx-auto pb-8">

        </div> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- DESKTOP LAYOUT -->
        <div class="relative hidden h-screen w-full md:flex">
            <!-- Login Panel Desktop -->
            <div
                class="relative flex w-1/2 items-center justify-center bg-[#DFECF2] p-8 transition-all duration-700 ease-in-out"
                :class="{ 'z-10 opacity-100': isLogin, 'z-0 opacity-0': !isLogin }"
            >
                <div class="w-full max-w-md">
                    <div class="mb-8 text-center">
                        <h2 class="mb-2 text-3xl font-bold text-gray-900">
                            Sign In to
                            <span class="font-pacifico bg-gradient-to-b from-[#22C1C3] to-[#41499F] bg-clip-text font-normal text-transparent"
                                >SEFTi.</span
                            >
                        </h2>
                    </div>

                    <div v-if="status" class="mb-4 rounded-lg border border-green-400 bg-green-100 p-3 text-sm text-green-700">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submitLogin" class="space-y-6">
                        <div class="space-y-2">
                            <div class="relative mt-6">
                                <Mail class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    id="login-email"
                                    type="email"
                                    v-model="loginForm.email"
                                    class="peer h-14 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                    placeholder=""
                                    required
                                />
                                <label
                                    for="login-email"
                                    class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                >
                                    Email<span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div v-if="loginForm.errors.email" class="text-xs text-red-500">
                                {{ loginForm.errors.email }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="relative mt-6">
                                <Lock class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    id="login-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="loginForm.password"
                                    class="peer h-14 border-0 border-b-2 border-gray-700 bg-transparent pr-10 pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                    placeholder=""
                                    required
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                >
                                    <Eye v-if="!showPassword" class="h-5 w-5" />
                                    <EyeOff v-else class="h-5 w-5" />
                                </button>
                                <label
                                    for="login-password"
                                    class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                >
                                    Password<span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div v-if="loginForm.errors.password" class="text-xs text-red-500">
                                {{ loginForm.errors.password }}
                            </div>
                        </div>

                        <!-- <div class="text-right">
              <button v-if="canResetPassword" type="button" class="text-sm text-gray-900 hover:text-black">
                Lupa kata sandi?
              </button>
            </div> -->

                        <Button
                            type="submit"
                            class="h-14 w-full rounded-full bg-gray-800 font-medium text-white hover:bg-gray-700"
                            :disabled="loginForm.processing"
                        >
                            <LoaderCircle v-if="loginForm.processing" class="mr-2 h-5 w-5 animate-spin" />
                            Sign In
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Register Panel Desktop -->
            <div
                class="relative flex w-1/2 items-center justify-center overflow-y-auto bg-[#DFECF2] p-8 transition-all duration-700 ease-in-out"
                :class="{ 'z-10 opacity-100': !isLogin, 'z-0 opacity-0': isLogin }"
            >
                <div class="w-full max-w-md py-8">
                    <div class="mb-8 text-center">
                        <h2 class="mb-2 text-3xl font-bold text-gray-900">Create Akun</h2>
                    </div>

                    <form @submit.prevent="submitRegister" class="space-y-4">
                        <div class="space-y-2">
                            <div class="relative mt-6">
                                <User class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    id="register-name"
                                    type="text"
                                    v-model="registerForm.name"
                                    class="peer h-14 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                    placeholder=""
                                    required
                                />
                                <label
                                    for="register-name"
                                    class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                >
                                    Nama Lengkap<span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div v-if="registerForm.errors.name" class="text-xs text-red-500">
                                {{ registerForm.errors.name }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="relative mt-6">
                                <Mail class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    id="register-email"
                                    type="email"
                                    v-model="registerForm.email"
                                    class="peer h-14 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                    placeholder=""
                                    required
                                />
                                <label
                                    for="register-email"
                                    class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                >
                                    Email<span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div v-if="registerForm.errors.email" class="text-xs text-red-500">
                                {{ registerForm.errors.email }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="relative mt-6">
                                <Lock class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    id="register-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="registerForm.password"
                                    class="peer h-14 border-0 border-b-2 border-gray-700 bg-transparent pr-10 pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                    placeholder=""
                                    required
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                >
                                    <Eye v-if="!showPassword" class="h-5 w-5" />
                                    <EyeOff v-else class="h-5 w-5" />
                                </button>
                                <label
                                    for="register-password"
                                    class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                >
                                    Password<span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div v-if="registerForm.errors.password" class="text-xs text-red-500">
                                {{ registerForm.errors.password }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="relative mt-6">
                                <Phone class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <Input
                                    id="register-phone"
                                    type="tel"
                                    v-model="registerForm.phone"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    @input="handlePhoneInput"
                                    class="peer h-14 border-0 border-b-2 border-gray-700 bg-transparent pl-10 text-gray-700 placeholder-transparent focus:border-cyan-500 focus:ring-0"
                                    placeholder=""
                                    required
                                />
                                <label
                                    for="register-phone"
                                    class="pointer-events-none absolute top-0 left-10 -translate-y-0 transform text-xs text-gray-700 duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700"
                                >
                                    Phone<span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div v-if="registerForm.errors.phone" class="text-xs text-red-500">
                                {{ registerForm.errors.phone }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="relative mt-6" ref="roleDropdownRef">
                                <Shield class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <div
                                    class="peer relative flex h-14 w-full cursor-pointer appearance-none items-center border-0 border-b-2 border-gray-700 bg-transparent pr-8 pl-10 text-gray-700 placeholder-transparent focus-within:border-cyan-500 focus-within:ring-0"
                                    @click="
                                        roleDropdownOpen = !roleDropdownOpen;
                                        if (roleDropdownOpen) departmentDropdownOpen = false;
                                    "
                                    tabindex="0"
                                >
                                    <span :class="[registerForm.role_id ? 'text-gray-700' : 'text-gray-400']">
                                        {{ props.roles.find((r) => r.id.toString() === registerForm.role_id)?.name || '' }}
                                    </span>
                                    <svg
                                        class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <label
                                        for="register-role"
                                        :class="[
                                            'pointer-events-none absolute left-10 transform duration-200',
                                            !registerForm.role_id
                                                ? 'top-1/2 -translate-y-1/2 text-base text-gray-400'
                                                : 'top-0 -translate-y-0 text-xs text-gray-700',
                                        ]"
                                    >
                                        Role<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div
                                    v-if="roleDropdownOpen"
                                    class="absolute top-full left-0 z-[9999] mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                                >
                                    <div
                                        v-for="role in props.roles"
                                        :key="role.id"
                                        @click="
                                            registerForm.role_id = role.id.toString();
                                            roleDropdownOpen = false;
                                        "
                                        class="cursor-pointer px-4 py-3 text-gray-700 first:rounded-t-xl last:rounded-b-xl hover:bg-cyan-50 hover:text-cyan-700"
                                        :class="{ 'bg-cyan-50 font-semibold text-cyan-700': registerForm.role_id === role.id.toString() }"
                                    >
                                        {{ role.name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="relative mt-6" ref="departmentDropdownRef">
                                <Building class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                                <div
                                    class="peer relative flex h-14 w-full cursor-pointer appearance-none items-center border-0 border-b-2 border-gray-700 bg-transparent pr-8 pl-10 text-gray-700 placeholder-transparent focus-within:border-cyan-500 focus-within:ring-0"
                                    @click="
                                        departmentDropdownOpen = !departmentDropdownOpen;
                                        if (departmentDropdownOpen) roleDropdownOpen = false;
                                    "
                                    tabindex="0"
                                >
                                    <span :class="[selectedDepartments.length > 0 ? 'text-gray-700' : 'text-gray-400']">
                                        {{ selectedDepartmentNames || '' }}
                                    </span>
                                    <svg
                                        class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <label
                                        for="register-department"
                                        :class="[
                                            'pointer-events-none absolute left-10 transform duration-200',
                                            selectedDepartments.length === 0
                                                ? 'top-1/2 -translate-y-1/2 text-base text-gray-400'
                                                : 'top-0 -translate-y-0 text-xs text-gray-700',
                                        ]"
                                    >
                                        Department<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div
                                    v-if="departmentDropdownOpen"
                                    class="absolute top-full left-0 z-[9999] mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                                >
                                    <div
                                        v-for="department in props.departments"
                                        :key="department.id"
                                        @click.stop
                                        class="flex cursor-pointer items-center gap-3 px-4 py-3 text-gray-700 first:rounded-t-xl last:rounded-b-xl hover:bg-cyan-50"
                                    >
                                        <input
                                            type="checkbox"
                                            :id="`dept-${department.id}`"
                                            :value="department.id.toString()"
                                            v-model="selectedDepartments"
                                            class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-cyan-600 focus:ring-2 focus:ring-cyan-500"
                                        />
                                        <label :for="`dept-${department.id}`" class="flex-1 cursor-pointer">
                                            {{ department.name }}
                                        </label>
                                    </div>
                                </div>
                                <div v-if="registerForm.errors.department_ids" class="mt-1 text-xs text-red-500">
                                    {{ registerForm.errors.department_ids }}
                                </div>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            class="mt-6 h-14 w-full rounded-full bg-gray-800 font-medium text-white hover:bg-gray-700"
                            :disabled="registerForm.processing"
                        >
                            <LoaderCircle v-if="registerForm.processing" class="mr-2 h-5 w-5 animate-spin" />
                            Sign Up
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Sliding Background Panel -->
            <div
                class="absolute top-0 h-full w-1/2 bg-gradient-to-br from-gray-800 via-gray-900 to-black text-white transition-all duration-800 ease-in-out"
                :class="{ 'left-1/2': isLogin, 'left-0': !isLogin }"
            >
                <div class="absolute inset-0 opacity-15">
                    <div class="absolute top-8 left-8">
                        <div class="h-24 w-24 rounded-full border-2 border-white opacity-60"></div>
                        <div class="absolute top-2 left-2 h-20 w-20 rounded-full border-2 border-white opacity-40"></div>
                        <div class="absolute top-4 left-4 h-16 w-16 rounded-full border-2 border-white opacity-30"></div>
                        <div class="absolute top-6 left-6 h-12 w-12 rounded-full border-2 border-white opacity-20"></div>
                    </div>

                    <div class="absolute top-16 right-16">
                        <div
                            class="h-0 w-0 rotate-45 transform border-r-8 border-b-12 border-l-8 border-r-transparent border-b-white border-l-transparent"
                        ></div>
                        <div
                            class="absolute top-4 left-4 h-0 w-0 -rotate-12 transform border-r-6 border-b-8 border-l-6 border-r-transparent border-b-white border-l-transparent"
                        ></div>
                        <div
                            class="absolute -top-2 -right-2 h-0 w-0 rotate-90 transform border-r-4 border-b-6 border-l-4 border-r-transparent border-b-white border-l-transparent"
                        ></div>
                    </div>

                    <div class="absolute top-1/3 left-12">
                        <div class="h-24 w-16 rotate-12 transform border-2 border-white opacity-50"></div>
                        <div class="absolute top-2 left-2 h-20 w-12 -rotate-6 transform border-2 border-white opacity-40"></div>
                    </div>

                    <div class="absolute top-1/2 right-20">
                        <div
                            class="h-0 w-0 border-r-6 border-b-10 border-l-6 border-r-transparent border-b-white border-l-transparent opacity-60"
                        ></div>
                        <div
                            class="absolute top-3 -left-2 h-0 w-0 border-r-4 border-b-6 border-l-4 border-r-transparent border-b-white border-l-transparent opacity-50"
                        ></div>
                        <div
                            class="absolute top-3 left-2 h-0 w-0 border-r-4 border-b-6 border-l-4 border-r-transparent border-b-white border-l-transparent opacity-50"
                        ></div>
                        <div
                            class="absolute top-6 left-0 h-0 w-0 border-r-3 border-b-4 border-l-3 border-r-transparent border-b-white border-l-transparent opacity-40"
                        ></div>
                    </div>

                    <div class="absolute bottom-24 left-20">
                        <div class="h-8 w-8 rounded-full bg-white opacity-60"></div>
                        <div class="absolute -top-2 -right-2 h-4 w-4 rounded-full bg-white opacity-40"></div>
                        <div class="absolute top-6 left-6 h-3 w-3 rounded-full bg-white opacity-50"></div>
                    </div>

                    <div class="absolute right-12 bottom-16">
                        <div class="h-20 w-20 rounded-full border-2 border-white opacity-40"></div>
                        <div class="absolute top-2 left-2 h-16 w-16 rounded-full border border-white opacity-30"></div>
                        <div class="absolute top-6 left-6 h-8 w-8 rounded-full bg-white opacity-60"></div>
                    </div>

                    <div class="absolute top-1/4 left-1/3 h-2 w-2 rounded-full bg-white opacity-50"></div>
                    <div class="absolute top-2/3 left-1/4 h-1.5 w-1.5 rounded-full bg-white opacity-60"></div>
                    <div class="absolute top-1/2 left-2/3 h-1 w-1 rounded-full bg-white opacity-70"></div>
                    <div class="absolute top-3/4 right-1/3 h-2.5 w-2.5 rounded-full bg-white opacity-40"></div>

                    <div class="absolute top-1/4 left-1/4 h-0.5 w-32 rotate-45 transform bg-white opacity-30"></div>
                    <div class="absolute right-1/4 bottom-1/3 h-0.5 w-24 -rotate-45 transform bg-white opacity-30"></div>
                    <div class="absolute top-1/3 right-1/2 h-0.5 w-20 rotate-12 transform bg-white opacity-25"></div>
                    <div class="absolute bottom-1/4 left-1/3 h-0.5 w-28 -rotate-12 transform bg-white opacity-25"></div>

                    <div class="absolute top-2/3 left-1/2 h-6 w-6 rotate-45 transform border-2 border-white opacity-40"></div>
                    <div class="absolute top-1/6 right-1/2 h-8 w-4 rotate-30 transform border border-white opacity-35"></div>

                    <div class="absolute bottom-1/3 left-1/6">
                        <div class="h-0.5 w-6 rotate-45 transform bg-white opacity-40"></div>
                        <div class="h-0.5 w-6 -rotate-45 transform bg-white opacity-40"></div>
                    </div>
                    <div class="absolute top-1/5 left-2/3">
                        <div class="h-0.5 w-4 rotate-45 transform bg-white opacity-30"></div>
                        <div class="h-0.5 w-4 -rotate-45 transform bg-white opacity-30"></div>
                    </div>
                </div>

                <div class="absolute top-8 left-8 z-10">
                    <div class="text-lg font-normal">
                        <span class="font-pacifico text-white">SGT.</span>
                    </div>
                </div>

                <div class="flex h-full items-center justify-center">
                    <div class="z-10 p-8 text-center">
                        <h1 class="mb-6 text-5xl font-bold">
                            <span v-if="isLogin">Hello, Friend !</span>
                            <span v-else>
                                Welcome to
                                <span class="font-pacifico bg-gradient-to-b from-[#22C1C3] to-[#41499F] bg-clip-text font-normal text-transparent"
                                    >SEFTi.</span
                                >
                            </span>
                        </h1>
                        <p v-if="isLogin" class="mx-auto mb-8 max-w-md text-lg text-gray-300">
                            Masukkan detail pribadi Anda dan mulailah perjalanan bersama
                            <span class="bg-gradient-to-b from-[#22C1C3] to-[#41499F] bg-clip-text text-transparent">SEFTi.</span>
                        </p>
                        <p v-else class="relative mx-auto mb-8 flex h-7 max-w-md items-center justify-center overflow-hidden text-lg text-gray-300">
                            <Transition name="slide-up" mode="out-in">
                                <span v-if="showTagline" :key="taglineIndex">{{ registerTaglines[taglineIndex] }}</span>
                            </Transition>
                        </p>
                        <Button
                            @click="toggleForm"
                            variant="outline"
                            class="rounded-full border-2 border-white bg-[#333333] px-8 py-3 text-base font-medium text-white hover:bg-white hover:text-gray-900"
                        >
                            <span v-if="isLogin">Sign Up</span>
                            <span v-else>Sign In</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- OTP Modal Overlay -->
        <div v-if="showOtpModal" class="fixed inset-0 z-[10000] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative mx-auto w-full max-w-lg">
                <OtpVerification :phone="modalOtpPhone" :asModal="true" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.transition-all {
    transition: all 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.focus\:border-cyan-500:focus {
    border-color: #06b6d4;
    box-shadow: none;
}

select {
    background-image: none;
}

.bg-gradient-to-br {
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.z-0 {
    z-index: 0;
}

.z-10 {
    z-index: 10;
}

.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 8px;
}

.scrollbar-track-transparent::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    background: transparent;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 1.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: block;
    position: absolute;
    width: 100%;
}

.slide-up-enter-from {
    opacity: 0;
    transform: translateY(100%);
}

.slide-up-enter-to {
    opacity: 1;
    transform: translateY(0);
}

.slide-up-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.slide-up-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}
</style>
