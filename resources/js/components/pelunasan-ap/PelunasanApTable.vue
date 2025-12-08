<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              v-if="hasDraftItems"
              class="px-6 py-4 text-center align-middle"
            >
              <input
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                v-model="selectAll"
                @change="toggleSelectAll"
              />
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              No. PL
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Tanggal
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              No. Referensi
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Supplier
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Status
            </th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="pelunasanAp in data" :key="pelunasanAp.id" class="alternating-row">
            <td
              v-if="hasDraftItems"
              class="px-6 py-4 text-center align-middle whitespace-nowrap"
            >
              <input
                v-if="pelunasanAp.status === 'Draft'"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                :value="pelunasanAp.id"
                v-model="selectedIds"
              />
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ pelunasanAp.no_pl || '-' }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatDate(pelunasanAp.tanggal) }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ pelunasanAp.bank_keluar?.no_bk || pelunasanAp.bank_mutasi?.no_mutasi || '-' }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ pelunasanAp.supplier?.nama_supplier || '-' }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              <span
                :class="getStatusBadgeClass(pelunasanAp.status)"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ pelunasanAp.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit -->
                <button
                  v-if="pelunasanAp.status === 'Draft'"
                  @click="emitAction('edit', pelunasanAp)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200"
                  title="Edit"
                >
                  <svg
                    class="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 01-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>

                <!-- Delete -->
                <button
                  v-if="pelunasanAp.status === 'Draft'"
                  @click="deletePelunasanAp(pelunasanAp.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Hapus"
                >
                  <svg
                    class="w-4 h-4 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>

                <!-- Detail -->
                <button
                  @click="emitAction('detail', pelunasanAp)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200"
                  title="Detail"
                >
                  <svg
                    class="w-4 h-4 text-green-600"
                    viewBox="0 0 16 16"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path d="M15 1H1V3H15V1Z" fill="currentColor" />
                    <path
                      d="M11 5H1V7H6.52779C7.62643 5.7725 9.223 5 11 5Z"
                      fill="currentColor"
                    />
                    <path
                      d="M5.34141 13C5.60482 13.7452 6.01127 14.4229 6.52779 15H1V13H5.34141Z"
                      fill="currentColor"
                    />
                    <path
                      d="M5.34141 9C5.12031 9.62556 5 10.2987 5 11H1V9H5.34141Z"
                      fill="currentColor"
                    />
                    <path
                      d="M15 11C15 11.7418 14.7981 12.4365 14.4462 13.032L15.9571 14.5429L14.5429 15.9571L13.032 14.4462C12.4365 14.7981 11.7418 15 11 15C8.79086 15 7 13.2091 7 11C7 8.79086 8.79086 7 11 7C13.2091 7 15 8.79086 15 11Z"
                      fill="currentColor"
                    />
                  </svg>
                </button>

                <!-- Log -->
                <button
                  @click="emitAction('log', pelunasanAp)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
                  title="Log Aktivitas"
                >
                  <svg
                    class="w-4 h-4 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="pagination"
      class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
    >
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="handlePagination(pagination.prev_page_url)"
          :disabled="!pagination.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pagination.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <!-- Page Numbers -->
        <template
          v-for="(link, index) in (pagination.links || []).slice(1, -1)"
          :key="index"
        >
          <button
            @click="handlePagination(link.url)"
            :disabled="!link.url"
            :class="[
              'w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200',
              link.active
                ? 'bg-black text-white'
                : link.url
                ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed',
            ]"
            v-html="link.label"
          ></button>
        </template>

        <!-- Next Button -->
        <button
          @click="handlePagination(pagination.next_page_url)"
          :disabled="!pagination.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pagination.next_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Next
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { getStatusBadgeClass } from '@/lib/status'

interface PelunasanApRow {
	id: number
	no_pl?: string
	tanggal?: string
  status: string
  bank_keluar?: { no_bk?: string } | null
  bank_mutasi?: { no_mutasi?: string } | null
  supplier?: { nama_supplier?: string } | null
}

interface PaginationLink {
  url?: string | null
  label: string
  active: boolean
}

interface PelunasanApPagination {
	data: PelunasanApRow[]
	from: number
	to: number
	total: number
	prev_page_url?: string | null
	next_page_url?: string | null
	links?: PaginationLink[]
}

interface Column {
	key: string
	label: string
	checked: boolean
	sortable?: boolean
}

const props = defineProps<{
	data: PelunasanApRow[]
	pagination: PelunasanApPagination | null
	selected: number[]
	columns: Column[]
}>()

const emit = defineEmits<{
	(e: 'select', value: number[]): void
	(e: 'action', payload: { action: string; row: PelunasanApRow }): void
	(e: 'paginate', url: string): void
	(e: 'add'): void
}>()

const selectedIds = ref<number[]>([...props.selected])
const selectAll = ref(false)

const hasDraftItems = computed(() => {
	return props.data.some(p => p.status === 'Draft')
})

const toggleSelectAll = () => {
	if (selectAll.value) {
		selectedIds.value = props.data
			.filter(p => p.status === 'Draft')
			.map(p => p.id)
	} else {
		selectedIds.value = []
	}
	emit('select', selectedIds.value)
}

const formatDate = (date?: string) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: '2-digit',
  })
}

const deletePelunasanAp = (id: number) => {
	if (!confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) return
	const row = props.data.find((r: PelunasanApRow) => r.id === id)
	if (row) emit('action', { action: 'delete', row })
}

const handlePagination = (url?: string | null) => {
	if (!url) return
	emit('paginate', url)
}

const emitAction = (action: string, row: PelunasanApRow) => {
	emit('action', { action, row })
}
</script>

<style scoped>
/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Ensure action column stays visible during horizontal scroll */
.sticky {
  position: sticky;
  z-index: 10;
}

/* Alternating row colors */
.alternating-row:nth-child(even) {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

/* Hover effect for alternating rows */
.alternating-row:nth-child(even):hover {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover {
  background-color: #f8fafc;
}

/* Action cell background matching parent row */
.alternating-row:nth-child(even) .action-cell {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) .action-cell {
  background-color: #ffffff;
}

.alternating-row:nth-child(even):hover .action-cell {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover .action-cell {
  background-color: #f8fafc;
}
</style>
