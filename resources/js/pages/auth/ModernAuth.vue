<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff, Mail, Lock, User, Phone, Building, Shield } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    departments: Array<{ id: number; name: string; status: string }>;
    roles: Array<{ id: number; name: string; description: string; permissions: string[]; status: string }>;
}>();

const isLogin = ref(true);
const showPassword = ref(false);

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
    department_id: '',
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

const toggleForm = () => {
    isLogin.value = !isLogin.value;
};

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
window.addEventListener('scroll', () => {
  if (roleDropdownOpen.value) updateRoleDropdownPosition();
  if (departmentDropdownOpen.value) updateDepartmentDropdownPosition();
}, true);

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
  'Transformasi Digital untuk Keuangan Anda.'
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
    }, 400); // jeda 400ms sebelum teks baru muncul
  }, 2500);
});
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
  if (taglineInterval) clearInterval(taglineInterval);
});

</script>

<template>
    <Head title="Authentication" />
    <div class="min-h-screen w-full bg-[#333333]">
        <div class="flex h-screen w-full relative">

                <!-- Login Panel -->
                <div class="w-1/2 flex items-center justify-center p-8 bg-[#DFECF2] relative transition-all duration-700 ease-in-out"
                     :class="{ 'opacity-100 z-10': isLogin, 'opacity-0 z-0': !isLogin }">
                    <div class="w-full max-w-md">
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                                Sign In to <span class="font-normal font-pacifico bg-gradient-to-b from-[#22C1C3] to-[#41499F] text-transparent bg-clip-text">SEFTi.</span>
                            </h2>
                        </div>

                        <div v-if="status" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
                            {{ status }}
                        </div>

                        <form @submit.prevent="submitLogin" class="space-y-6">
                            <div class="space-y-2">
                                <div class="relative mt-6">
                                    <Mail class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <Input
                                        id="login-email"
                                        type="email"
                                        v-model="loginForm.email"
                                        class="pl-10 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus:border-cyan-500 focus:ring-0 text-gray-700 placeholder-transparent peer"
                                        placeholder=""
                                        required
                                    />
                                    <label
                                        for="login-email"
                                        class="absolute left-10 duration-200 transform pointer-events-none
                                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400
                                            peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700
                                            top-0 -translate-y-0 text-xs text-gray-700"
                                    >
                                        Email<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div v-if="loginForm.errors.email" class="text-red-500 text-xs">{{ loginForm.errors.email }}</div>
                            </div>

                            <div class="space-y-2">
                                <div class="relative mt-6">
                                    <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <Input
                                        id="login-password"
                                        :type="showPassword ? 'text' : 'password'"
                                        v-model="loginForm.password"
                                        class="pl-10 pr-10 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus:border-cyan-500 focus:ring-0 text-gray-700 placeholder-transparent peer"
                                        placeholder=""
                                        required
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        <Eye v-if="!showPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                    <label
                                        for="login-password"
                                        class="absolute left-10 duration-200 transform pointer-events-none
                                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400
                                            peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700
                                            top-0 -translate-y-0 text-xs text-gray-700"
                                    >
                                        Password<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div v-if="loginForm.errors.password" class="text-red-500 text-xs">{{ loginForm.errors.password }}</div>
                            </div>

                            <div class="text-right">
                                <button
                                    v-if="canResetPassword"
                                    type="button"
                                    class="text-sm text-gray-900 hover:text-black"
                                >
                                    Lupa kata sandi?
                                </button>
                            </div>

                            <Button type="submit" class="w-full h-14 bg-gray-800 hover:bg-gray-700 text-white font-medium rounded-full" :disabled="loginForm.processing">
                                <LoaderCircle v-if="loginForm.processing" class="w-5 h-5 animate-spin mr-2" />
                                Sign In
                            </Button>

                            <!-- <div class="text-center">
                                <p class="text-gray-600 text-sm">Atau</p>
                            </div>

                            <Button type="button" variant="outline" class="w-full h-14 border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-full">
                                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Sign in with Google
                            </Button> -->
                        </form>
                    </div>
                </div>

                <!-- Register Panel -->
                <div class="w-1/2 flex items-center justify-center p-8 bg-[#DFECF2] relative transition-all duration-700 ease-in-out"
                     :class="{ 'opacity-100 z-10': !isLogin, 'opacity-0 z-0': isLogin }">
                    <div class="w-full max-w-md">
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Akun</h2>
                        </div>

                        <form @submit.prevent="submitRegister" class="space-y-4">
                            <!-- Name -->
                            <div class="space-y-2">
                                <div class="relative mt-6">
                                    <User class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <Input
                                        id="register-name"
                                        type="text"
                                        v-model="registerForm.name"
                                        class="pl-10 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus:border-cyan-500 focus:ring-0 text-gray-700 placeholder-transparent peer"
                                        placeholder=""
                                        required
                                    />
                                    <label
                                        for="register-name"
                                        class="absolute left-10 duration-200 transform pointer-events-none
                                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400
                                            peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700
                                            top-0 -translate-y-0 text-xs text-gray-700"
                                    >
                                        Nama Lengkap<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div v-if="registerForm.errors.name" class="text-red-500 text-xs">{{ registerForm.errors.name }}</div>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <div class="relative mt-6">
                                    <Mail class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <Input
                                        id="register-email"
                                        type="email"
                                        v-model="registerForm.email"
                                        class="pl-10 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus:border-cyan-500 focus:ring-0 text-gray-700 placeholder-transparent peer"
                                        placeholder=""
                                        required
                                    />
                                    <label
                                        for="register-email"
                                        class="absolute left-10 duration-200 transform pointer-events-none
                                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400
                                            peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700
                                            top-0 -translate-y-0 text-xs text-gray-700"
                                    >
                                        Email<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div v-if="registerForm.errors.email" class="text-red-500 text-xs">{{ registerForm.errors.email }}</div>
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <div class="relative mt-6">
                                    <Lock class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <Input
                                        id="register-password"
                                        :type="showPassword ? 'text' : 'password'"
                                        v-model="registerForm.password"
                                        class="pl-10 pr-10 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus:border-cyan-500 focus:ring-0 text-gray-700 placeholder-transparent peer"
                                        placeholder=""
                                        required
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        <Eye v-if="!showPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                    <label
                                        for="register-password"
                                        class="absolute left-10 duration-200 transform pointer-events-none
                                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400
                                            peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700
                                            top-0 -translate-y-0 text-xs text-gray-700"
                                    >
                                        Password<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div v-if="registerForm.errors.password" class="text-red-500 text-xs">{{ registerForm.errors.password }}</div>
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <div class="relative mt-6">
                                    <Phone class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <Input
                                        id="register-phone"
                                        type="tel"
                                        v-model="registerForm.phone"
                                        class="pl-10 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus:border-cyan-500 focus:ring-0 text-gray-700 placeholder-transparent peer"
                                        placeholder=""
                                        required
                                    />
                                    <label
                                        for="register-phone"
                                        class="absolute left-10 duration-200 transform pointer-events-none
                                            peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400
                                            peer-focus:top-0 peer-focus:-translate-y-0 peer-focus:text-xs peer-focus:text-gray-700
                                            top-0 -translate-y-0 text-xs text-gray-700"
                                    >
                                        Phone<span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div v-if="registerForm.errors.phone" class="text-red-500 text-xs">{{ registerForm.errors.phone }}</div>
                            </div>

                            <!-- Role -->
                            <div class="space-y-2">
                                <div class="relative mt-6" ref="roleDropdownRef">
                                    <Shield class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <div
                                        class="w-full pl-10 pr-8 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus-within:border-cyan-500 focus-within:ring-0 appearance-none text-gray-700 placeholder-transparent peer flex items-center cursor-pointer relative"
                                        @click="roleDropdownOpen = !roleDropdownOpen; if (roleDropdownOpen) departmentDropdownOpen = false"
                                        tabindex="0"
                                    >
                                        <span :class="[registerForm.role_id ? 'text-gray-700' : 'text-gray-400']">
                                            {{ props.roles.find(r => r.id.toString() === registerForm.role_id)?.name || '' }}
                                        </span>
                                        <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <label
                                            for="register-role"
                                            :class="[
                                                'absolute left-10 duration-200 transform pointer-events-none',
                                                !registerForm.role_id
                                                    ? 'top-1/2 -translate-y-1/2 text-base text-gray-400'
                                                    : 'top-0 -translate-y-0 text-xs text-gray-700'
                                            ]"
                                        >
                                            Role<span class="text-red-500">*</span>
                                        </label>
                                    </div>
                                    <teleport to="body">
                                        <div v-if="roleDropdownOpen" :style="roleDropdownStyle" class="bg-white rounded-xl shadow-lg border border-gray-200 max-h-56 overflow-y-auto z-50">
                                            <div
                                                v-for="role in props.roles"
                                                :key="role.id"
                                                @click="registerForm.role_id = role.id.toString(); roleDropdownOpen = false"
                                                class="px-4 py-3 cursor-pointer text-gray-700 hover:bg-cyan-50 hover:text-cyan-700 first:rounded-t-xl last:rounded-b-xl"
                                                :class="{ 'bg-cyan-50 text-cyan-700 font-semibold': registerForm.role_id === role.id.toString() }"
                                            >
                                                {{ role.name }}
                                            </div>
                                        </div>
                                    </teleport>
                                </div>
                            </div>

                            <!-- Department -->
                            <div class="space-y-2">
                                <div class="relative mt-6" ref="departmentDropdownRef">
                                    <Building class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                                    <div
                                        class="w-full pl-10 pr-8 h-14 bg-transparent border-0 border-b-2 border-gray-700 focus-within:border-cyan-500 focus-within:ring-0 appearance-none text-gray-700 placeholder-transparent peer flex items-center cursor-pointer relative"
                                        @click="departmentDropdownOpen = !departmentDropdownOpen; if (departmentDropdownOpen) roleDropdownOpen = false"
                                        tabindex="0"
                                    >
                                        <span :class="[registerForm.department_id ? 'text-gray-700' : 'text-gray-400']">
                                            {{ props.departments.find(d => d.id.toString() === registerForm.department_id)?.name || '' }}
                                        </span>
                                        <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <label
                                            for="register-department"
                                            :class="[
                                                'absolute left-10 duration-200 transform pointer-events-none',
                                                !registerForm.department_id
                                                    ? 'top-1/2 -translate-y-1/2 text-base text-gray-400'
                                                    : 'top-0 -translate-y-0 text-xs text-gray-700'
                                            ]"
                                        >
                                            Department<span class="text-red-500">*</span>
                                        </label>
                                    </div>
                                    <teleport to="body">
                                        <div v-if="departmentDropdownOpen" :style="departmentDropdownStyle" class="bg-white rounded-xl shadow-lg border border-gray-200 max-h-56 overflow-y-auto z-50">
                                            <div
                                                v-for="department in props.departments"
                                                :key="department.id"
                                                @click="registerForm.department_id = department.id.toString(); departmentDropdownOpen = false"
                                                class="px-4 py-3 cursor-pointer text-gray-700 hover:bg-cyan-50 hover:text-cyan-700 first:rounded-t-xl last:rounded-b-xl"
                                                :class="{ 'bg-cyan-50 text-cyan-700 font-semibold': registerForm.department_id === department.id.toString() }"
                                            >
                                                {{ department.name }}
                                            </div>
                                        </div>
                                    </teleport>
                                </div>
                            </div>

                            <Button type="submit" class="w-full h-14 bg-gray-800 hover:bg-gray-700 text-white font-medium rounded-full mt-6" :disabled="registerForm.processing">
                                <LoaderCircle v-if="registerForm.processing" class="w-5 h-5 animate-spin mr-2" />
                                Sign Up
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- Sliding Background Panel -->
                <div class="absolute top-0 w-1/2 h-full bg-gradient-to-br from-gray-800 via-gray-900 to-black text-white transition-all duration-800 ease-in-out"
                     :class="{ 'left-1/2': isLogin, 'left-0': !isLogin }">

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
                                <span v-if="isLogin">Hello, Friend !</span>
                                <span v-else>Welcome to <span class="font-normal font-pacifico bg-gradient-to-b from-[#22C1C3] to-[#41499F] text-transparent bg-clip-text">SEFTi.</span></span>
                            </h1>
                            <p v-if="isLogin" class="text-lg text-gray-300 mb-8 max-w-md mx-auto">
                                Masukkan detail pribadi Anda dan mulailah perjalanan bersama <span class="bg-gradient-to-b from-[#22C1C3] to-[#41499F] text-transparent bg-clip-text">SEFTi.</span>
                            </p>
                            <p v-else class="text-lg text-gray-300 mb-8 max-w-md mx-auto h-7 overflow-hidden flex items-center justify-center relative">
                                <Transition name="slide-up" mode="out-in">
                                    <span v-if="showTagline" :key="taglineIndex">{{ registerTaglines[taglineIndex] }}</span>
                                </Transition>
                            </p>
                            <Button
                                @click="toggleForm"
                                variant="outline"
                                class="border-2 bg-[#333333] border-white text-white hover:bg-white hover:text-gray-900 px-8 py-3 text-base font-medium rounded-full"
                            >
                                <span v-if="isLogin">Sign Up</span>
                                <span v-else>Sign In</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>

<style scoped>
/* Custom transitions */
.transition-all {
    transition: all 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Input focus states */
.focus\:border-cyan-500:focus {
    border-color: #06b6d4;
    box-shadow: none;
}

/* Custom select styling */
select {
    background-image: none;
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

/* Custom scrollbar for dropdown */
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

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 1.2s cubic-bezier(.4,0,.2,1);
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
