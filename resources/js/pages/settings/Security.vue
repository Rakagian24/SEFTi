<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
// import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { useMessagePanel } from '@/composables/useMessagePanel';
import { useAlertDialog } from '@/composables/useAlertDialog';

const page = usePage();
const hasPasscode = computed(() => page.props.has_passcode);
const returnUrl = computed(() => (page.props as any).return as string | undefined);
const actionData = computed(() => (page.props as any).action_data as string | undefined);

const form = useForm({
    old_passcode: '',
    passcode: '',
    passcode_confirmation: '',
});

const showOldPasscode = ref(false);
const showPasscode = ref(false);
const showPasscodeConfirmation = ref(false);

const initialForm = { ...form.data() };

const isMobile = /Mobi|Android/i.test(navigator.userAgent);

const validatePasscode = (val: string) => /^\d{6}$/.test(val);

const onlyNumberKey = (e: KeyboardEvent) => {
    // Izinkan: backspace, delete, tab, escape, enter, arrow keys
    if (
        [46, 8, 9, 27, 13, 110, 190].includes(e.keyCode) ||
        // Allow: Ctrl/cmd+A
        (e.keyCode === 65 && (e.ctrlKey || e.metaKey)) ||
        // Allow: Ctrl/cmd+C
        (e.keyCode === 67 && (e.ctrlKey || e.metaKey)) ||
        // Allow: Ctrl/cmd+V
        (e.keyCode === 86 && (e.ctrlKey || e.metaKey)) ||
        // Allow: Ctrl/cmd+X
        (e.keyCode === 88 && (e.ctrlKey || e.metaKey)) ||
        // Allow: home, end, left, right
        (e.keyCode >= 35 && e.keyCode <= 39)
    ) {
        return;
    }
    // Cegah jika bukan angka
    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
        e.preventDefault();
    }
};

const sanitizeNumberInput = (field: 'old_passcode' | 'passcode' | 'passcode_confirmation') => {
    form[field] = form[field].replace(/[^0-9]/g, '').slice(0, 6);
};

const { addSuccess, addError } = useMessagePanel();
const { showInfo } = useAlertDialog();

const submit = () => {
    // Validasi manual sebelum submit
    if (hasPasscode.value && !validatePasscode(form.old_passcode)) {
        form.setError('old_passcode', 'Passcode lama harus 6 digit angka');
        return;
    }
    if (!validatePasscode(form.passcode)) {
        form.setError('passcode', 'Passcode harus 6 digit angka');
        return;
    }
    if (form.passcode !== form.passcode_confirmation) {
        form.setError('passcode_confirmation', 'Konfirmasi passcode tidak cocok');
        return;
    }
    form.clearErrors();

    const params: any = {};
    if (returnUrl.value) {
        params.return = returnUrl.value;
    }
    if (actionData.value) {
        params.action_data = actionData.value;
    }
    const url = Object.keys(params).length > 0
        ? route('settings.passcode.update', params)
        : route('settings.passcode.update');

    form.put(url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            addSuccess('Passcode berhasil diubah!');

            // 🔑 Reload props auth.user biar langsung sync dengan passcode baru
            router.reload({ only: ['auth'] });
        },
        onError: () => {
            addError('Gagal mengubah passcode!');
        }
    });
};


const resetForm = () => {
    form.old_passcode = initialForm.old_passcode;
    form.passcode = initialForm.passcode;
    form.passcode_confirmation = initialForm.passcode_confirmation;
    form.clearErrors();
};

const addFingerprint = () => {
    showInfo('Fitur fingerprint belum tersedia.', 'Informasi');
};

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Security',
        href: '/settings/security',
    },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Security" />
    <SettingsLayout>
      <div class="space-y-6">
        <div class="border-b border-gray-200 pb-4">
          <h2 class="text-xl font-semibold text-gray-900">Security</h2>
          <div class="w-8 h-0.5 bg-[rgba(51,51,51,0.5)] mt-2"></div>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
          <div v-if="hasPasscode" class="floating-input">
            <input
              id="old_passcode"
              v-model="form.old_passcode"
              :type="showOldPasscode ? 'text' : 'password'"
              class="floating-input-field floating-input-field--with-icon"
              inputmode="numeric"
              maxlength="6"
              autocomplete="off"
              placeholder=" "
              @keydown="onlyNumberKey"
              @input="sanitizeNumberInput('old_passcode')"
            />
            <button
              type="button"
              class="floating-input-toggle"
              @click="showOldPasscode = !showOldPasscode"
              tabindex="-1"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                class="w-5 h-5 text-gray-400"
              >
                <path
                  d="M2.25 12C3.5 7.5 7.5 4.5 12 4.5c4.5 0 8.5 3 9.75 7.5-1.25 4.5-5.25 7.5-9.75 7.5-4.5 0-8.5-3-9.75-7.5Z"
                />
                <path
                  d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                  :fill="showOldPasscode ? 'currentColor' : 'none'"
                />
              </svg>
            </button>
            <label for="old_passcode" class="floating-label">Passcode Lama</label>
            <InputError :message="form.errors.old_passcode" />
          </div>
          <div class="floating-input">
            <input
              id="passcode"
              v-model="form.passcode"
              :type="showPasscode ? 'text' : 'password'"
              class="floating-input-field floating-input-field--with-icon"
              inputmode="numeric"
              maxlength="6"
              autocomplete="off"
              placeholder=" "
              @keydown="onlyNumberKey"
              @input="sanitizeNumberInput('passcode')"
            />
            <button
              type="button"
              class="floating-input-toggle"
              @click="showPasscode = !showPasscode"
              tabindex="-1"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                class="w-5 h-5 text-gray-400"
              >
                <path
                  d="M2.25 12C3.5 7.5 7.5 4.5 12 4.5c4.5 0 8.5 3 9.75 7.5-1.25 4.5-5.25 7.5-9.75 7.5-4.5 0-8.5-3-9.75-7.5Z"
                />
                <path
                  d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                  :fill="showPasscode ? 'currentColor' : 'none'"
                />
              </svg>
            </button>
            <label for="passcode" class="floating-label">Passcode Baru</label>
            <InputError :message="form.errors.passcode" />
          </div>
          <div class="floating-input">
            <input
              id="passcode_confirmation"
              v-model="form.passcode_confirmation"
              :type="showPasscodeConfirmation ? 'text' : 'password'"
              class="floating-input-field floating-input-field--with-icon"
              inputmode="numeric"
              maxlength="6"
              autocomplete="off"
              placeholder=" "
              @keydown="onlyNumberKey"
              @input="sanitizeNumberInput('passcode_confirmation')"
            />
            <button
              type="button"
              class="floating-input-toggle"
              @click="showPasscodeConfirmation = !showPasscodeConfirmation"
              tabindex="-1"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                class="w-5 h-5 text-gray-400"
              >
                <path
                  d="M2.25 12C3.5 7.5 7.5 4.5 12 4.5c4.5 0 8.5 3 9.75 7.5-1.25 4.5-5.25 7.5-9.75 7.5-4.5 0-8.5-3-9.75-7.5Z"
                />
                <path
                  d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                  :fill="showPasscodeConfirmation ? 'currentColor' : 'none'"
                />
              </svg>
            </button>
            <label for="passcode_confirmation" class="floating-label"
              >Konfirmasi Passcode</label
            >
            <InputError :message="form.errors.passcode_confirmation" />
          </div>
          <div v-if="isMobile" class="grid gap-2">
            <Button type="button" @click="addFingerprint" variant="outline"
              >Add Fingerprint</Button
            >
          </div>
          <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-start">
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
.floating-input-field--with-icon {
  padding-right: 2.5rem;
}
.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.floating-input-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  padding: 0;
  cursor: pointer;
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
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #1f9254;
  transform: translateY(0) scale(1);
}
</style>
