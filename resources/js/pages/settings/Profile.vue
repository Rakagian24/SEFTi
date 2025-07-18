<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { User } from 'lucide-vue-next';
import { useMessagePanel } from '@/composables/useMessagePanel';

// Tambahkan type User lokal agar property photo, phone, role, department dikenali
interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    photo?: string;
    role?: { name: string };
    department?: { name: string };
    email_verified_at?: string | null;
}

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const initialUser = {
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    password: '',
    photo: user.photo || '',
};

const form = useForm<{
    name: string;
    email: string;
    phone: string;
    password: string;
    photo: File | null;
}>({
    name: initialUser.name,
    email: initialUser.email,
    phone: initialUser.phone,
    password: '',
    photo: null,
});

const photoPreview = ref(initialUser.photo ? `/storage/${initialUser.photo}` : 'lucide');
const photoFile = ref<File|null>(null);
const photoError = ref('');

const showPassword = ref(false);

const firstError = computed(() => {
  const keys = Object.keys(form.errors) as Array<'name' | 'email' | 'phone' | 'password' | 'photo'>;
  return keys.length ? form.errors[keys[0]] : '';
});

const { addSuccess, addError } = useMessagePanel();

function handlePhotoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] || null;
    photoError.value = '';
    if (!file) return;
    // Validasi format dan size
    if (!['image/jpeg', 'image/png'].includes(file.type)) {
        photoError.value = 'Format foto harus JPEG atau PNG';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        photoError.value = 'Ukuran foto maksimal 2MB';
        return;
    }
    photoFile.value = file;
    form.photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function resetPhoto() {
    photoFile.value = null;
    form.photo = null;
    photoPreview.value = initialUser.photo ? `/storage/${initialUser.photo}` : 'lucide';
    photoError.value = '';
}

function resetForm() {
    form.name = initialUser.name;
    form.email = initialUser.email;
    form.phone = initialUser.phone;
    form.password = '';
    resetPhoto();
    form.clearErrors();
}

const submit = () => {
    photoError.value = '';

    form.transform((data) => {
        const formData = new FormData();
        formData.append('name', data.name);
        formData.append('email', data.email);
        formData.append('phone', data.phone);
        formData.append('password', data.password);
        if (data.photo) {
            formData.append('photo', data.photo);
        }
        formData.append('_method', 'PATCH'); // agar Laravel mengenali sebagai PATCH
        return formData;
    }).post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            addSuccess('Profil berhasil diperbarui!');
        },
        onError: () => {
            addError('Gagal memperbarui profil!');
        }
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Profile settings" />

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <!-- Profile Header -->
        <div class="border-b border-gray-200 pb-4">
          <h2 class="text-xl font-semibold text-gray-900">My Profile</h2>
          <div class="w-8 h-0.5 bg-[rgba(51,51,51,0.5)] mt-2"></div>
        </div>
        <div v-if="form.hasErrors" class="rounded bg-red-100 border border-red-300 text-red-800 px-4 py-2 mb-2">
          {{ firstError }}
        </div>

        <!-- Profile Avatar and Basic Info -->
        <div class="flex items-start space-x-6">
          <div class="relative">
            <div
              class="w-24 h-24 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center overflow-hidden"
            >
              <img
                v-if="photoPreview && photoPreview !== 'lucide'"
                :src="photoPreview"
                alt="Profile"
                class="w-24 h-24 rounded-full object-cover"
              />
              <User v-else class="w-16 h-16 text-white/80" />
            </div>
            <!-- Camera icon for edit -->
            <label
              class="absolute bottom-0 right-0 w-6 h-6 bg-gray-600 rounded-full flex items-center justify-center cursor-pointer"
            >
              <svg
                class="w-3 h-3 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                ></path>
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                ></path>
              </svg>
              <input
                type="file"
                accept="image/jpeg,image/png"
                class="hidden"
                @change="handlePhotoChange"
              />
            </label>
            <button
              v-if="photoFile"
              @click="resetPhoto"
              class="absolute left-0 bottom-0 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-xs"
            >
              ×
            </button>
          </div>
          <div v-if="photoError" class="text-xs text-red-500 mt-2">{{ photoError }}</div>
        </div>

        <!-- Profile Form -->
        <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name Field -->
            <div class="floating-input">
              <input
                id="name"
                type="text"
                class="floating-input-field"
                v-model="form.name"
                required
                autocomplete="name"
                placeholder=" "
              />
              <label for="name" class="floating-label">
                Nama Lengkap
              </label>
              <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <!-- Phone Field -->
            <div class="floating-input">
              <input
                id="phone"
                type="tel"
                class="floating-input-field"
                v-model="form.phone"
                placeholder=" "
              />
              <label for="phone" class="floating-label"> No. Telepon </label>
              <InputError class="mt-1" :message="form.errors.phone" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Email Field -->
            <div class="floating-input">
              <input
                id="email"
                type="email"
                class="floating-input-field"
                v-model="form.email"
                required
                autocomplete="username"
                placeholder=" "
              />
              <label for="email" class="floating-label">
                Email
              </label>
              <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Role Field (Read-only) -->
            <div class="floating-input">
              <input
                type="text"
                class="floating-input-field bg-[rgba(217,217,217,0.3)] cursor-not-allowed"
                :value="user.role?.name || 'Admin'"
                readonly
                placeholder=" "
              />
              <label class="floating-label"> Role </label>
            </div>
          </div>

          <!-- Password Field -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                id="password"
                :type="showPassword ? 'text' : 'password'"
                class="floating-input-field pr-10"
                v-model="form.password"
                placeholder=" "
              />
              <label for="password" class="floating-label"> Password </label>
              <button
                type="button"
                @click="togglePasswordVisibility"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg
                  v-if="showPassword"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  ></path>
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.543 7-1.275 4.057-5.065 7-9.543 7-4.477 0-8.268-2.943-9.542-7z"
                  ></path>
                </svg>
              </button>
              <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Department Field (Read-only) -->
            <div class="floating-input">
              <input
                type="text"
                class="floating-input-field bg-[rgba(217,217,217,0.3)] cursor-not-allowed"
                :value="user.department?.name || 'System Development'"
                readonly
                placeholder=" "
              />
              <label class="floating-label"> Department </label>
            </div>
          </div>

          <!-- Email Verification Notice -->
          <div
            v-if="mustVerifyEmail && !user.email_verified_at"
            class="bg-yellow-50 border border-yellow-200 rounded-md p-4"
          >
            <p class="text-sm text-yellow-800">
              Your email address is unverified.
              <Link
                :href="route('verification.send')"
                method="post"
                as="button"
                class="text-blue-600 underline hover:text-blue-800 font-medium"
              >
                Click here to resend the verification email.
              </Link>
            </p>

            <div
              v-if="status === 'verification-link-sent'"
              class="mt-2 text-sm font-medium text-green-600"
            >
              A new verification link has been sent to your email address.
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center gap-4 pt-4">
            <button
              type="submit"
              class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                fill="#E6E6E6"
                height="24"
                viewBox="0 0 24 24"
                width="24"
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
              >
                <path
                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
                />
              </svg>
              Simpan
            </button>

            <button
              type="button"
              @click="resetForm"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="size-6"
              >
                <path
                  fill-rule="evenodd"
                  d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                  clip-rule="evenodd"
                />
              </svg>
              Batal
            </button>
          </div>
        </form>
      </div>

      <!-- Delete User Component (if needed) -->
      <!-- <DeleteUser /> -->
    </SettingsLayout>
  </AppLayout>
</template>

<style scoped>
.floating-input {
  position: relative;
  margin-top: 1rem;
}

.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.3s ease-in-out;
}

.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}

/* When input is focused or has value - label goes to border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #1f9254;
  transform: translateY(0) scale(1);
}

/* For readonly fields */
.floating-input-field:read-only ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #6b7280;
  transform: translateY(0) scale(1);
}

/* Hover effects */
.floating-input:hover .floating-input-field:not(:read-only) {
  border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
  border-color: #1f9254;
}

/* Make sure the label background covers the border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input-field:read-only ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}

/* Additional styles for better UX */
.transition-all {
  transition: all 0.3s ease;
}

/* Special styling for readonly fields */
.floating-input-field:read-only {
  background-color: rgba(217, 217, 217, 0.3);
  cursor: not-allowed;
}
</style>
