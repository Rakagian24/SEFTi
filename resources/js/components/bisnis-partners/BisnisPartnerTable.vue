<script setup lang="ts">
defineProps({ arPartners: Object });
const emit = defineEmits(['edit', 'delete', 'detail', 'log', 'paginate']);

function editRow(row: any) {
  emit('edit', row);
}

function deleteRow(row: any) {
  emit('delete', row);
}

function detailRow(row: any) {
  emit('detail', row);
}

function logRow(row: any) {
  emit('log', row);
}

function goToPage(url: string) {
  emit('paginate', url);
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Nama BP
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Jenis BP
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Alamat
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Email
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              No. Telepon
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Nama Bank
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Nama Rekening
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              No. Rekening/VA
            </th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
              Terms Of Payment
            </th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap sticky right-0 bg-gray-50">
              Action
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="row in arPartners?.data" :key="row.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ row.nama_bp }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ row.jenis_bp }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
              <div class="truncate" :title="row.alamat">
                {{ row.alamat }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
              {{ row.email }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ row.no_telepon }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ row.nama_bank }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ row.nama_rekening }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ row.no_rekening }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ row.terms_of_payment }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 bg-white">
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit Button -->
                <button
                  @click="editRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200"
                  title="Edit"
                >
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>

                <!-- Delete Button -->
                <button
                  @click="deleteRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Hapus"
                >
                  <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>

                <!-- Detail Button -->
                <button
                  @click="detailRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200"
                  title="Detail"
                >
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>

                <!-- Log Activity Button -->
                <button
                  @click="logRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
                  title="Log Activity"
                >
                  <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="goToPage(arPartners?.prev_page_url)"
          :disabled="!arPartners?.prev_page_url"
          :class="[
            'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md',
            arPartners?.prev_page_url ? 'text-gray-700 bg-white hover:bg-gray-50' : 'text-gray-300 bg-gray-50 cursor-not-allowed'
          ]"
        >
          Previous
        </button>
        <button
          @click="goToPage(arPartners?.next_page_url)"
          :disabled="!arPartners?.next_page_url"
          :class="[
            'ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md',
            arPartners?.next_page_url ? 'text-gray-700 bg-white hover:bg-gray-50' : 'text-gray-300 bg-gray-50 cursor-not-allowed'
          ]"
        >
          Next
        </button>
      </div>

      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Showing
            <span class="font-medium">{{ arPartners?.from || 0 }}</span>
            to
            <span class="font-medium">{{ arPartners?.to || 0 }}</span>
            of
            <span class="font-medium">{{ arPartners?.total || 0 }}</span>
            results
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <!-- Previous Button -->
            <button
              @click="goToPage(arPartners?.prev_page_url)"
              :disabled="!arPartners?.prev_page_url"
              :class="[
                'relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium',
                arPartners?.prev_page_url ? 'text-gray-500 hover:bg-gray-50' : 'text-gray-300 cursor-not-allowed'
              ]"
            >
              <span class="sr-only">Previous</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </button>

            <!-- Page Numbers -->
            <template v-for="(link, index) in arPartners?.links?.slice(1, -1)" :key="index">
              <button
                @click="goToPage(link.url)"
                :disabled="!link.url"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  link.active
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : link.url
                      ? 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                      : 'bg-white border-gray-300 text-gray-300 cursor-not-allowed'
                ]"
                v-html="link.label"
              ></button>
            </template>

            <!-- Next Button -->
            <button
              @click="goToPage(arPartners?.next_page_url)"
              :disabled="!arPartners?.next_page_url"
              :class="[
                'relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium',
                arPartners?.next_page_url ? 'text-gray-500 hover:bg-gray-50' : 'text-gray-300 cursor-not-allowed'
              ]"
            >
              <span class="sr-only">Next</span>
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

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
</style>
