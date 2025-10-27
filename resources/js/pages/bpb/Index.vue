<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import BpbFilter from "@/components/bpb/BpbFilter.vue";
import BpbTable from "@/components/bpb/BpbTable.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Send } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { usePage } from "@inertiajs/vue3";
import { getIconForPage } from "@/lib/iconMapping";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bukti Penerimaan Barang" },
];

defineOptions({ layout: AppLayout });

const rows = ref<any[]>([]);
const meta = ref<any>({});
const selected = ref<number[]>([]);
const loading = ref(false);
const { addSuccess, addError, clearAll } = useMessagePanel();

const page = usePage();
const departmentOptions = computed<any[]>(() => (page.props as any).departmentOptions || []);
const supplierOptions = computed<any[]>(() => (page.props as any).supplierOptions || []);

// Column config shared between Filter and Table
type Column = { key: string; label: string; checked: boolean; sortable?: boolean };
const columns = ref<Column[]>([
  { key: "no_bpb", label: "No. BPB", checked: true, sortable: true },
  { key: "no_po", label: "No. PO", checked: true, sortable: true },
  { key: "no_pv", label: "No. PV", checked: true, sortable: true },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
]);

function updateColumns(c: Column[]) {
  columns.value = Array.isArray(c) ? c : [];
}

// Confirm dialog state
const showConfirmSend = ref(false);
const showConfirmCancel = ref(false);
const cancelTargetId = ref<number | null>(null);

function fetchData(params: any = {}) {
  loading.value = true;
  router.get(
    "/bpb",
    params,
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page: any) => {
        const props = (page as any).props as any;
        rows.value = props.bpbs?.data || [];
        meta.value = props.bpbs || {};
      },
      onFinish: () => (loading.value = false),
    }
  );
}

function onFilter(payload: any) {
  fetchData(payload);
}

function onReset() {
  fetchData({});
}

function onPaginate(url: string) {
  if (!url) return;
  loading.value = true;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page: any) => {
      const props = (page as any).props as any;
      rows.value = props.bpbs?.data || [];
      meta.value = props.bpbs || {};
    },
    onFinish: () => (loading.value = false),
  });
}

function onSend() {
  if (selected.value.length === 0) return;
  showConfirmSend.value = true;
}

function confirmSend() {
  clearAll();
  router.post(
    "/bpb/send",
    { ids: selected.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        addSuccess("Dokumen berhasil dikirim");
        fetchData({});
        selected.value = [];
      },
      onError: (err: any) => {
        addError((err && (Object.values(err).flat() as any).join(" ")) || "Gagal mengirim dokumen");
      },
      onFinish: () => (showConfirmSend.value = false),
    }
  );
}

function cancelSend() {
  showConfirmSend.value = false;
}

function onAction(e: { action: string; row: any }) {
  const { action, row } = e;
  if (action === "edit") {
    router.visit(`/bpb/${row.id}/edit`);
  } else if (action === "cancel") {
    cancelTargetId.value = row.id;
    showConfirmCancel.value = true;
  } else if (action === "detail") {
    router.visit(`/bpb/${row.id}/detail`);
  } else if (action === "download") {
    window.open(`/bpb/${row.id}/download`, "_blank");
  } else if (action === "log") {
    router.visit(`/bpb/${row.id}/log`);
  }
}

function confirmCancel() {
  if (!cancelTargetId.value) return;
  clearAll();
  axios
    .post(`/bpb/${cancelTargetId.value}/cancel`)
    .then(() => {
      addSuccess("Dokumen dibatalkan");
      fetchData({});
    })
    .catch((err) => {
      const serverErrors = err?.response?.data?.errors;
      if (serverErrors && typeof serverErrors === 'object') {
        const messages = (Object.values(serverErrors) as any).flat().join(' ');
        addError(messages || 'Gagal membatalkan dokumen');
      } else {
        addError(err?.response?.data?.message || 'Gagal membatalkan dokumen');
      }
    })
    .finally(() => {
      showConfirmCancel.value = false;
      cancelTargetId.value = null;
    });
}

function cancelCancel() {
  showConfirmCancel.value = false;
  cancelTargetId.value = null;
}

onMounted(() => fetchData({}));
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bukti Penerimaan Barang</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <component :is="getIconForPage('Bukti Penerimaan Barang')" class="w-4 h-4 mr-1" />
            Manage Bukti Penerimaan Barang data
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="onSend"
            :disabled="selected.length === 0"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" />
            Kirim ({{ selected.length }})
          </button>

          <button
            @click="router.visit('/bpb/create')"
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

      <BpbFilter
        :department-options="departmentOptions"
        :supplier-options="supplierOptions"
        :columns="columns"
        @filter="onFilter"
        @reset="onReset"
        @update:columns="updateColumns"
      />

      <BpbTable
        :data="rows"
        :pagination="meta"
        :columns="columns"
        @select="(ids: number[]) => selected = ids"
        @action="onAction"
        @paginate="onPaginate"
      />

      <StatusLegend entity="BPB" />

    <!-- Confirm Dialogs -->
    <ConfirmDialog
      :show="showConfirmSend"
      :message="`Apakah Anda yakin ingin mengirim ${selected.length} dokumen BPB?`"
      @confirm="confirmSend"
      @cancel="cancelSend"
    />
    <ConfirmDialog
      :show="showConfirmCancel"
      message="Apakah Anda yakin ingin membatalkan dokumen BPB ini?"
      @confirm="confirmCancel"
      @cancel="cancelCancel"
    />
    </div>
  </div>
</template>
