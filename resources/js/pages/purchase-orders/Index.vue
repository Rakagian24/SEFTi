<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <ShoppingCart class="w-4 h-4 mr-1" />
            Manage Purchase Order data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Bulk Actions -->
          <div v-if="selected.length > 0" class="flex items-center gap-2">
            <button
              @click="sendSelected"
              :disabled="!canSend"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="w-4 h-4" />
              Kirim ({{ selected.length }})
            </button>
          </div>

          <!-- Add New Button -->
          <button
            @click="goToAdd"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Add New
          </button>
        </div>
      </div>

      <!-- Filter Section -->
      <PurchaseOrderFilter
        :filters="filters"
        :departments="departments"
        :perihals="perihals"
        v-model:search="searchQuery"
        v-model:status="statusFilter"
        v-model:supplier="supplierFilter"
        v-model:date-from="dateFromFilter"
        v-model:date-to="dateToFilter"
        v-model:entries-per-page="perPage"
        @reset="onResetFilter"
      />

      <!-- Table Section -->
      <PurchaseOrderTable
        :data="data"
        :loading="loading"
        :selected="selected"
        @select="onSelect"
        @edit="handleEdit"
        @delete="handleDelete"
        @detail="handleDetail"
        @download="handleDownload"
        @log="handleLog"
        @paginate="handlePagination"
        @add="goToAdd"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import PurchaseOrderTable from "../../components/purchase-orders/PurchaseOrderTable.vue";
import PurchaseOrderFilter from "../../components/purchase-orders/PurchaseOrderFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { ShoppingCart, Send } from "lucide-vue-next";
import type { Router } from "vue-router";
import axios from "axios";

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Purchase Order" }];

defineOptions({ layout: AppLayout });

const router: Router = useRouter();
const { addSuccess, addError } = useMessagePanel();

const data = ref([]);
const loading = ref(false);
const filters = ref({});
const selected = ref<number[]>([]);
const page = ref(1);
const perPage = ref(10);
const total = ref(0);

// Filter reactive refs
const searchQuery = ref("");
const statusFilter = ref("");
const supplierFilter = ref("");
const dateFromFilter = ref("");
const dateToFilter = ref("");

// Master data
const departments = ref<any[]>([]);
const perihals = ref<any[]>([]);

const totalPages = computed(() => Math.ceil(total.value / perPage.value));
const canSend = computed(() => selected.value.length > 0);

// Watch filters with debouncing (matching supplier pattern)
let searchTimeout: ReturnType<typeof setTimeout>;
watch(
  [statusFilter, supplierFilter, dateFromFilter, dateToFilter, perPage],
  () => {
    updateFilters();
  },
  { immediate: false }
);

// Watch search query with debouncing (matching supplier pattern)
watch(
  searchQuery,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      updateFilters();
    }, 500); // 500ms debounce
  },
  { immediate: false }
);

function updateFilters() {
  const newFilters: Record<string, any> = {};

  if (searchQuery.value) newFilters.search = searchQuery.value;
  if (statusFilter.value) newFilters.status = statusFilter.value;
  if (supplierFilter.value) newFilters.supplier = supplierFilter.value;
  if (dateFromFilter.value) newFilters.date_from = dateFromFilter.value;
  if (dateToFilter.value) newFilters.date_to = dateToFilter.value;

  filters.value = newFilters;
  page.value = 1;
  fetchData();
}

function fetchData() {
  loading.value = true;
  axios
    .get("/purchase-orders", {
      params: {
        ...filters.value,
        page: page.value,
        per_page: perPage.value,
      },
    })
    .then((res) => {
      data.value = res.data.data;
      total.value = res.data.total;
      selected.value = [];
    })
    .catch(() => {
      addError("Terjadi kesalahan saat memuat data");
    })
    .finally(() => (loading.value = false));
}

async function fetchMasters() {
  try {
    const [deptRes, perihalRes] = await Promise.all([
      axios.get("/departments"),
      axios.get("/perihals"),
    ]);
    departments.value = deptRes.data.data;
    perihals.value = perihalRes.data.data;
  } catch {
    addError("Gagal memuat master departemen/perihal");
  }
}

function changePage(newPage: number) {
  if (newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage;
    fetchData();
  }
}

function handlePagination(url: string) {
  if (!url) return;

  const urlParams = new URLSearchParams(url.split("?")[1]);
  const newPage = urlParams.get("page");

  if (newPage) {
    changePage(parseInt(newPage));
  }
}

function onResetFilter() {
  filters.value = {};
  searchQuery.value = "";
  statusFilter.value = "";
  supplierFilter.value = "";
  dateFromFilter.value = "";
  dateToFilter.value = "";
  page.value = 1;
  perPage.value = 10;
  fetchData();
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleEdit(row: any) {
  router.push({ name: "purchase-orders.edit", params: { id: row.id } });
}

function handleDelete(row: any) {
  // Direct delete without confirmation (following supplier pattern)
  axios
    .delete(`/purchase-orders/${row.id}`)
    .then(() => {
      addSuccess("Purchase Order berhasil dihapus");
      fetchData();
    })
    .catch(() => {
      addError("Terjadi kesalahan saat menghapus data");
    });
}

function handleDetail(row: any) {
  router.push({ name: "purchase-orders.show", params: { id: row.id } });
}

function handleDownload(row: any) {
  window.open(`/purchase-orders/${row.id}/download`, "_blank");
}

function handleLog(row: any) {
  router.push({ name: "purchase-orders.logs", params: { id: row.id } });
}

function sendSelected() {
  if (!canSend.value) return;

  axios
    .post("/purchase-orders/send", { ids: selected.value })
    .then(() => {
      addSuccess(`${selected.value.length} Purchase Order berhasil dikirim`);
      fetchData();
    })
    .catch(() => {
      addError("Terjadi kesalahan saat mengirim Purchase Order");
    });
}

function goToAdd() {
  router.push({ name: "purchase-orders.create" });
}

onMounted(() => {
  fetchData();
  fetchMasters();
});
</script>
