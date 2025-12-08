<template>
  <div class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>

    <!-- Panel -->
    <div class="relative mx-auto mt-10 max-w-5xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">Pilih Dokumen Referensi</h2>
        <button
          type="button"
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700"
        >
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>

      <!-- Toolbar -->
      <div class="px-6 py-3 flex items-center gap-2">
        <input
          v-model="filterDate"
          type="date"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Filter tanggal"
        />
        <button
          type="button"
          @click="applyFilter"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Filter
        </button>
        <div class="ml-auto flex items-center gap-3">
          <div class="text-sm text-gray-600">
            Menampilkan {{ documents.length }} data
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="px-6 pb-2 max-h-[28rem] overflow-auto">
        <table class="w-full text-sm table-auto">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="py-2 px-3 w-40">No. BK</th>
              <th class="py-2 px-3 w-28">Tanggal</th>
              <th class="py-2 px-3 w-48">Departemen</th>
              <th class="py-2 px-3 w-40">Rekening</th>
              <th class="py-2 px-3 w-32 text-right">Nilai</th>
              <th class="py-2 px-3 w-24 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="doc in documents"
              :key="doc.id"
              class="border-t border-gray-100 transition-colors hover:bg-blue-50/50"
            >
              <td class="py-3 px-3 font-medium text-gray-900">{{ doc.no_bk }}</td>
              <td class="py-3 px-3 text-gray-700">{{ formatDate(doc.tanggal) }}</td>
              <td class="py-3 px-3 text-gray-700">{{ doc.department?.name || '-' }}</td>
              <td class="py-3 px-3 text-gray-700">
                {{ doc.nama_pemilik_rekening || doc.no_rekening || '-' }}
              </td>
              <td class="py-3 px-3 text-right font-medium text-gray-900">{{ formatCurrency(doc.nominal ?? doc.nilai) }}</td>
              <td class="py-3 px-3 text-center">
                <button
                  type="button"
                  @click="selectDoc(doc)"
                  class="px-3 py-1 text-xs font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Pilih
                </button>
              </td>
            </tr>

            <tr v-if="documents.length === 0">
              <td colspan="6" class="py-10 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <svg
                    class="w-12 h-12 mb-3 text-gray-300"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                  </svg>
                  <div class="text-base font-medium mb-1">
                    Tidak ada Dokumen yang tersedia
                  </div>
                  <div class="text-sm">Silakan coba filter tanggal yang berbeda</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-center">
        <nav class="flex items-center space-x-2" aria-label="Pagination">
          <!-- Previous Button -->
          <button
            type="button"
            @click="prevPage"
            :disabled="currentPage === 1"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
              currentPage === 1
                ? 'text-gray-400 cursor-not-allowed'
                : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50',
            ]"
          >
            Previous
          </button>

          <!-- Page indicator -->
          <div class="px-4 py-2 text-sm text-gray-600">
            Halaman {{ currentPage }}
          </div>

          <!-- Next Button -->
          <button
            type="button"
            @click="nextPage"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
              'text-gray-600 hover:text-gray-800 hover:bg-gray-50',
            ]"
          >
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Icon from '@/components/Icon.vue'

interface ReferenceDocument {
  id: number
  no_bk?: string
  tanggal?: string
  department?: { name?: string }
  no_rekening?: string
  nama_pemilik_rekening?: string
  nominal?: number
  nilai?: number
}

const props = defineProps<{
  departmentId?: number | string | null
}>()

const emit = defineEmits(['select', 'close'])

const documents = ref<ReferenceDocument[]>([])
const filterDate = ref('')
const currentPage = ref(1)

onMounted(() => {
  fetchDocuments()
})

const fetchDocuments = async () => {
  try {
    const response = await axios.get(route('pelunasan-ap.bank-keluars.search'), {
      params: {
        tanggal_start: filterDate.value,
        page: currentPage.value,
        department_id: props.departmentId || undefined,
      },
    })
    documents.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching documents:', error)
  }
}

const applyFilter = () => {
  currentPage.value = 1
  fetchDocuments()
}

const selectDoc = (doc: any) => {
  emit('select', doc)
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    fetchDocuments()
  }
}

const nextPage = () => {
  currentPage.value++
  fetchDocuments()
}

const formatDate = (date: any) => {
  if (!date) return '-'
  try {
    return new Date(date).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return String(date || '-')
  }
}

const formatCurrency = (value: any) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}
</script>
