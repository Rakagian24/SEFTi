<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  documents: any;
}>();

function formatDate(dateString: string | null | undefined) {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: '2-digit'
    });
  } catch {
    return dateString;
  }
}

const rows = computed(() => props.documents?.data || []);
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Dokumen List Bayar</h2>
    </div>
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              No List Bayar
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Tanggal
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Jumlah Dokumen PV
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="rows.length === 0">
            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
              Belum ada Dokumen List Bayar.
            </td>
          </tr>
          <tr
            v-for="doc in rows"
            :key="doc.id"
            class="alternating-row"
          >
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm font-medium text-[#101010]">
              {{ doc.no_list_bayar }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatDate(doc.tanggal) }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ doc.jumlah_pv }}
            </td>
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm">
              <a
                :href="`/list-bayar/documents/${doc.id}/edit`"
                class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-150"
              >
                Edit
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg" v-if="documents?.links?.length">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <a
          v-if="documents.links[0]?.url"
          :href="documents.links[0].url"
          class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 text-gray-600 hover:text-gray-800 hover:bg-gray-50"
        >
          Previous
        </a>
        <span
          v-else
          class="px-4 py-2 text-sm font-medium rounded-lg text-gray-400 cursor-not-allowed"
        >
          Previous
        </span>

        <!-- Page Numbers -->
        <template v-for="(link, index) in documents.links.slice(1, -1)" :key="index">
          <a
            v-if="link.url"
            :href="link.url"
            :class="[
              'w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200 flex items-center justify-center',
              link.active
                ? 'bg-black text-white'
                : 'bg-gray-200 text-gray-600 hover:bg-gray-300',
            ]"
            v-html="link.label"
          ></a>
          <span
            v-else
            :class="[
              'w-10 h-10 text-sm font-medium rounded-lg flex items-center justify-center',
              'bg-gray-200 text-gray-400 cursor-not-allowed',
            ]"
            v-html="link.label"
          ></span>
        </template>

        <!-- Next Button -->
        <a
          v-if="documents.links[documents.links.length - 1]?.url"
          :href="documents.links[documents.links.length - 1].url"
          class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 text-gray-600 hover:text-gray-800 hover:bg-gray-50"
        >
          Next
        </a>
        <span
          v-else
          class="px-4 py-2 text-sm font-medium rounded-lg text-gray-400 cursor-not-allowed"
        >
          Next
        </span>
      </nav>
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

/* Pagination styling enhancements */
nav a:focus,
nav span:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Hover effects for pagination buttons */
nav a:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
