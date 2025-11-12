<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" @click="close"></div>
    <div class="relative mx-auto mt-10 max-w-5xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">Pilih User</h2>
        <button type="button" @click="close" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-3 flex items-center gap-3">
        <div class="ml-auto relative">
          <input
            v-model="localQuery"
            @input="onSearchInput"
            type="text"
            placeholder="Cari user..."
            class="pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <svg class="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
          </svg>
        </div>
      </div>

      <div class="px-6 pb-4 max-h-[26rem] overflow-auto">
        <table class="w-full text-sm table-auto">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="py-2 px-3 w-56">Nama</th>
              <th class="py-2 px-3 w-56">Email</th>
              <th class="py-2 px-3 w-40">No. Telepon</th>
              <th class="py-2 px-3">Departments</th>
              <th class="py-2 px-3 w-24">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id" class="border-t border-gray-100">
              <td class="py-3 px-3 font-medium">{{ u.name }}</td>
              <td class="py-3 px-3 text-blue-600">{{ u.email }}</td>
              <td class="py-3 px-3">{{ u.phone || '-' }}</td>
              <td class="py-3 px-3">
                <span v-if="Array.isArray(u.departments) && u.departments.length" class="text-gray-700">
                  {{ u.departments.map((d:any)=>d.name).join(', ') }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="py-3 px-3">
                <button type="button" @click="select(u)" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Pilih</button>
              </td>
            </tr>
            <tr v-if="!loading && (!users || users.length===0)">
              <td colspan="5" class="py-10 text-center text-gray-500">Tidak ada data</td>
            </tr>
            <tr v-if="loading">
              <td colspan="5" class="py-10 text-center text-gray-500">Memuat...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
        <div class="flex-1">
          <Pagination v-if="pagination && Number(pagination.last_page) > 1" :pagination="pagination" @page-changed="onPageChanged" />
        </div>
        <div class="ml-4">
          <button type="button" @click="close" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Pagination from '@/components/ui/Pagination.vue'

interface PaginationData { current_page: number; last_page: number; per_page: number; total: number; from?: number; to?: number }

defineProps<{ open: boolean; users: any[]; loading?: boolean; pagination?: PaginationData }>()
const emit = defineEmits(['update:open', 'search', 'select', 'page-changed'])

const localQuery = ref('')
let searchTimeout: ReturnType<typeof setTimeout>

function close() {
  emit('update:open', false)
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => emit('search', localQuery.value), 300)
}

function select(user: any) {
  emit('select', user)
}

function onPageChanged(page: number) {
  emit('page-changed', page)
}
</script>
