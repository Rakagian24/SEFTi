<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-flex items-center gap-2 text-gray-500">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        Memuat data...
      </div>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <!-- Select All Checkbox -->
            <th class="px-6 py-3 text-left">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
            </th>

            <!-- Columns -->
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              No. MB
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Department
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Detail Keperluan
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Total
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Metode Pembayaran
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Tanggal
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="memo in data"
            :key="memo.id"
            :class="[
              'hover:bg-gray-50 transition-colors',
              selected.includes(memo.id) ? 'bg-blue-50' : '',
            ]"
          >
            <!-- Checkbox -->
            <td class="px-6 py-4 whitespace-nowrap">
              <input
                type="checkbox"
                :checked="selected.includes(memo.id)"
                :disabled="!isRowSelectable(memo)"
                @change="toggleSelect(memo.id)"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed"
              />
            </td>

            <!-- No. MB -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ memo.no_mb || "-" }}
                  </div>
                  <div class="text-sm text-gray-500">ID: {{ memo.id }}</div>
                </div>
              </div>
            </td>

            <!-- Department -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ memo.department?.name || "-" }}
              </div>
            </td>

            <!-- Detail Keperluan -->
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900 max-w-xs truncate">
                {{ memo.detail_keperluan || "-" }}
              </div>
            </td>

            <!-- Total -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">
                {{ formatCurrency(memo.total || 0) }}
              </div>
            </td>

            <!-- Metode Pembayaran -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getMetodePembayaranClass(memo.metode_pembayaran),
                ]"
              >
                {{ memo.metode_pembayaran || "-" }}
              </span>
            </td>

            <!-- Status -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getStatusClass(memo.status),
                ]"
              >
                <div
                  class="w-2 h-2 rounded-full mr-2"
                  :class="getStatusDotClass(memo.status)"
                ></div>
                {{ memo.status }}
              </span>
            </td>

            <!-- Tanggal -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(memo.tanggal) }}
            </td>

            <!-- Actions -->
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center gap-2">
                <!-- View Detail -->
                <button
                  @click="viewDetail(memo.id)"
                  class="text-blue-600 hover:text-blue-900 transition-colors"
                  title="Lihat Detail"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>

                <!-- View Log -->
                <button
                  @click="viewLog(memo.id)"
                  class="text-gray-600 hover:text-gray-900 transition-colors"
                  title="Lihat Log"
                >
                  <svg
                    class="w-4 h-4"
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

                <!-- Action Buttons -->
                <div class="flex items-center gap-1">
                  <!-- Verify Button -->
                  <button
                    v-if="canVerify(memo)"
                    @click="handleAction('verify', memo.id)"
                    class="text-yellow-600 hover:text-yellow-900 transition-colors"
                    title="Verify"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </button>

                  <!-- Validate Button -->
                  <button
                    v-if="canValidate(memo)"
                    @click="handleAction('validate', memo.id)"
                    class="text-purple-600 hover:text-purple-900 transition-colors"
                    title="Validate"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                      />
                    </svg>
                  </button>

                  <!-- Approve Button -->
                  <button
                    v-if="canApprove(memo)"
                    @click="handleAction('approve', memo.id)"
                    class="text-green-600 hover:text-green-900 transition-colors"
                    title="Approve"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                  </button>

                  <!-- Reject Button -->
                  <button
                    v-if="canReject(memo)"
                    @click="handleAction('reject', memo.id)"
                    class="text-red-600 hover:text-red-900 transition-colors"
                    title="Reject"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="!loading && data.length === 0" class="p-8 text-center">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
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
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
        <p class="mt-1 text-sm text-gray-500">
          Tidak ada Memo Pembayaran yang ditemukan dengan filter saat ini.
        </p>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="pagination && pagination.last_page > 1"
      class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
    >
      <div class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="paginate(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <button
            @click="paginate(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Menampilkan
              <span class="font-medium">{{
                (pagination.current_page - 1) * pagination.per_page + 1
              }}</span>
              sampai
              <span class="font-medium">
                {{
                  Math.min(
                    pagination.current_page * pagination.per_page,
                    pagination.total
                  )
                }}
              </span>
              dari
              <span class="font-medium">{{ pagination.total }}</span>
              hasil
            </p>
          </div>
          <div>
            <nav
              class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
              aria-label="Pagination"
            >
              <button
                @click="paginate(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>

              <template v-for="page in getPageNumbers()" :key="page">
                <button
                  v-if="page !== '...'"
                  @click="paginate(page)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === pagination.current_page
                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  ]"
                >
                  {{ page }}
                </button>
                <span
                  v-else
                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                >
                  ...
                </span>
              </template>

              <button
                @click="paginate(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { formatCurrency } from "@/lib/currencyUtils";

// Props
const props = defineProps<{
  data: any[];
  loading: boolean;
  selected: number[];
  pagination: any;
  selectableStatuses: string[];
  isRowSelectable: (memo: any) => boolean;
}>();

// Emits
const emit = defineEmits<{
  select: [memoId: number, selected: boolean];
  action: [actionData: { action: string; id: number; notes?: string; reason?: string }];
  paginate: [page: number];
}>();

// Computed
const isAllSelected = computed(() => {
  const selectableData = props.data.filter(props.isRowSelectable);
  return (
    selectableData.length > 0 &&
    selectableData.every((memo) => props.selected.includes(memo.id))
  );
});

// Methods
const toggleSelectAll = () => {
  const selectableData = props.data.filter(props.isRowSelectable);

  if (isAllSelected.value) {
    // Deselect all
    selectableData.forEach((memo) => {
      emit("select", memo.id, false);
    });
  } else {
    // Select all
    selectableData.forEach((memo) => {
      emit("select", memo.id, true);
    });
  }
};

const toggleSelect = (memoId: number) => {
  const isSelected = props.selected.includes(memoId);
  emit("select", memoId, !isSelected);
};

const viewDetail = (memoId: number) => {
  router.visit(`/approval/memo-pembayarans/${memoId}/detail`);
};

const viewLog = (memoId: number) => {
  router.visit(`/memo-pembayaran/${memoId}/log`);
};

const handleAction = (action: string, memoId: number) => {
  emit("action", { action, id: memoId });
};

const paginate = (page: number) => {
  emit("paginate", page);
};

const getPageNumbers = () => {
  if (!props.pagination) return [];

  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
  const pages = [];

  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i);
      }
      pages.push("...");
      pages.push(last);
    } else if (current >= last - 3) {
      pages.push(1);
      pages.push("...");
      for (let i = last - 4; i <= last; i++) {
        pages.push(i);
      }
    } else {
      pages.push(1);
      pages.push("...");
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i);
      }
      pages.push("...");
      pages.push(last);
    }
  }

  return pages;
};

function formatDate(date: string) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Action permission methods
const canVerify = (memo: any) => {
  return (
    memo.status === "In Progress" && props.selectableStatuses.includes("In Progress")
  );
};

const canValidate = (memo: any) => {
  return memo.status === "Verified" && props.selectableStatuses.includes("Verified");
};

const canApprove = (memo: any) => {
  return memo.status === "Validated" && props.selectableStatuses.includes("Validated");
};

const canReject = (memo: any) => {
  return ["In Progress", "Verified", "Validated"].includes(memo.status);
};

// Status styling methods
const getStatusClass = (status: string) => {
  switch (status) {
    case "Draft":
      return "bg-gray-100 text-gray-800";
    case "In Progress":
      return "bg-blue-100 text-blue-800";
    case "Verified":
      return "bg-yellow-100 text-yellow-800";
    case "Validated":
      return "bg-purple-100 text-purple-800";
    case "Approved":
      return "bg-green-100 text-green-800";
    case "Rejected":
      return "bg-red-100 text-red-800";
    case "Canceled":
      return "bg-gray-100 text-gray-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
};

const getStatusDotClass = (status: string) => {
  switch (status) {
    case "Draft":
      return "bg-gray-400";
    case "In Progress":
      return "bg-blue-400";
    case "Verified":
      return "bg-yellow-400";
    case "Validated":
      return "bg-purple-400";
    case "Approved":
      return "bg-green-400";
    case "Rejected":
      return "bg-red-400";
    case "Canceled":
      return "bg-gray-400";
    default:
      return "bg-gray-400";
  }
};

const getMetodePembayaranClass = (metode: string) => {
  switch (metode) {
    case "Transfer":
      return "bg-blue-100 text-blue-800";
    case "Cek/Giro":
      return "bg-green-100 text-green-800";
    case "Kredit":
      return "bg-purple-100 text-purple-800";
    case "Tunai":
      return "bg-yellow-100 text-yellow-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
};
</script>
