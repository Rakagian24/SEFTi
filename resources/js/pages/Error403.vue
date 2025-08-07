<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-100">
          <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Akses Ditolak
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
        </p>
      </div>

      <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Detail Error
          </h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Error 403 - Forbidden
          </p>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
          <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div class="sm:col-span-1">
              <dt class="text-sm font-medium text-gray-500">
                Role Anda
              </dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ user?.role?.name || 'Tidak ada role' }}
              </dd>
            </div>
            <div class="sm:col-span-1">
              <dt class="text-sm font-medium text-gray-500">
                Department
              </dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ user?.department?.name || 'Tidak ada department' }}
              </dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500">
                Permissions yang Anda miliki
              </dt>
              <dd class="mt-1 text-sm text-gray-900">
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="permission in user?.role?.permissions || []"
                    :key="permission"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    {{ permission }}
                  </span>
                  <span v-if="!user?.role?.permissions?.length" class="text-gray-500">
                    Tidak ada permissions
                  </span>
                </div>
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="flex flex-col space-y-3">
        <Link
          :href="route('dashboard')"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Kembali ke Dashboard
        </Link>

        <button
          @click="goBack"
          class="group relative w-full flex justify-center py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Kembali ke Halaman Sebelumnya
        </button>
      </div>

      <div class="text-center">
        <p class="text-xs text-gray-500">
          Jika Anda yakin ini adalah kesalahan, silakan hubungi administrator.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    window.location.href = route('dashboard')
  }
}
</script>
