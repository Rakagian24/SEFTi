<template>
  <div v-if="pagination.last_page > 1" class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
    <nav class="flex items-center space-x-2" aria-label="Pagination">
      <!-- Previous Button -->
      <button
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page <= 1"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
          pagination.current_page > 1
            ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
            : 'text-gray-400 cursor-not-allowed',
        ]"
      >
        Previous
      </button>

      <!-- Range Navigation (if more than 10 pages) -->
      <template v-if="pagination.last_page > 10">
        <button
          @click="changeRange('prev')"
          :disabled="pagination.current_page <= 10"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pagination.current_page > 10
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          «
        </button>
      </template>

      <!-- Page Numbers -->
      <template v-for="(page, index) in getPageNumbers()" :key="`page-${index}-${page}`">
        <button
          v-if="page !== '...'"
          @click="changePage(page as number)"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            Number(page) === Number(pagination.current_page)
              ? 'pagination-active'
              : 'pagination-inactive',
          ]"
          :title="`Page ${page} - Current: ${pagination.current_page} - Active: ${Number(page) === Number(pagination.current_page)}`"
        >
          {{ page === pagination.last_page ? pagination.last_page : page }}
        </button>
        <span v-else class="px-2 text-gray-400">...</span>
      </template>

      <!-- Range Navigation (if more than 10 pages) -->
      <template v-if="pagination.last_page > 10">
        <button
          @click="changeRange('next')"
          :disabled="pagination.current_page >= pagination.last_page - 9"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pagination.current_page < pagination.last_page - 9
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          »
        </button>
      </template>

      <!-- Next Button -->
      <button
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page >= pagination.last_page"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
          pagination.current_page < pagination.last_page
            ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
            : 'text-gray-400 cursor-not-allowed',
        ]"
      >
        Next
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
interface PaginationData {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number;
  to?: number;
}

interface Props {
  pagination: PaginationData;
}

interface Emits {
  (e: 'page-changed', page: number): void;
  (e: 'range-changed', direction: 'prev' | 'next'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function getPageNumbers() {
  const pages: (number | string)[] = [];
  const currentPage = Number(props.pagination.current_page);
  const lastPage = Number(props.pagination.last_page);

  if (lastPage <= 10) {
    // Jika total halaman <= 10, tampilkan semua halaman
    for (let i = 1; i <= lastPage; i++) {
      pages.push(i);
    }
  } else {
    // Jika total halaman > 10, gunakan logika range
    const startRange = Math.floor((currentPage - 1) / 10) * 10 + 1;
    const endRange = Math.min(startRange + 9, lastPage);
    const nextRange = endRange + 1;
    const prevRange = Math.max(1, startRange - 10);

    // Selalu tampilkan halaman 1
    pages.push(1);

    // Jika current page tidak di range 1-10, tambahkan ellipsis dan halaman range sebelumnya
    if (currentPage > 10) {
      pages.push('...');
      // Hanya tambahkan prevRange jika bukan 1
      if (prevRange !== 1) {
        pages.push(prevRange);
        pages.push('...');
      }
    }

    // Tampilkan range halaman saat ini (kecuali halaman 1 yang sudah ditampilkan)
    for (let i = startRange; i <= endRange; i++) {
      if (i !== 1) { // Jangan duplikasi halaman 1
        pages.push(i);
      }
    }

    // Jika ada range berikutnya dan bukan halaman terakhir, tambahkan ellipsis dan next range
    if (nextRange <= lastPage && nextRange !== lastPage) {
      pages.push('...');
      pages.push(nextRange);
      pages.push('...');
    }

    // Jika bukan halaman terakhir dan belum ditampilkan, tambahkan last page
    if (endRange < lastPage && !pages.includes(lastPage)) {
      pages.push(lastPage);
    }
  }

  return pages;
}

function changePage(page: number) {
  if (page >= 1 && page <= props.pagination.last_page) {
    emit('page-changed', page);
  }
}

function changeRange(direction: 'prev' | 'next') {
  emit('range-changed', direction);
}
</script>

<style scoped>
/* Pagination active state */
.pagination-active {
  background-color: #000000 !important;
  color: #ffffff !important;
  font-weight: 600 !important;
}

/* Pagination inactive state */
.pagination-inactive {
  background-color: #e5e7eb !important;
  color: #374151 !important;
}

.pagination-inactive:hover {
  background-color: #d1d5db !important;
}

/* Pagination styling enhancements */
nav button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
