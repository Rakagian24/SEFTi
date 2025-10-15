<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { router } from "@inertiajs/vue3";
import BpbFilter from "@/components/bpb/BpbFilter.vue";
import BpbTable from "@/components/bpb/BpbTable.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Package } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { usePage } from "@inertiajs/vue3";

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
  router.post(`/bpb/${cancelTargetId.value}/cancel`, {}, {
    onSuccess: () => {
      addSuccess("Dokumen dibatalkan");
      fetchData({});
    },
    onError: (err: any) => addError((err && (Object.values(err).flat() as any).join(" ")) || "Gagal membatalkan dokumen"),
    onFinish: () => {
      showConfirmCancel.value = false;
      cancelTargetId.value = null;
    },
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
            <Package class="w-4 h-4 mr-1" />
            Kelola dokumen Bukti Penerimaan Barang
          </div>
        </div>
        <div class="flex gap-2">
          <button
            class="px-4 py-2 rounded-lg bg-[#5856D6] text-white font-medium disabled:opacity-50 hover:bg-[#4745b8] transition-colors"
            :disabled="selected.length === 0"
            @click="onSend"
          >
            Kirim
          </button>
          <a 
            href="/bpb/create" 
            class="px-4 py-2 rounded-lg bg-black text-white font-medium hover:bg-gray-800 transition-colors"
          >
            Add New
          </a>
        </div>
      </div>

      <BpbFilter
        :department-options="departmentOptions"
        :supplier-options="supplierOptions"
        @filter="onFilter"
        @reset="onReset"
      />

      <BpbTable
        :data="rows"
        :pagination="meta"
        @select="(ids: number[]) => selected = ids"
        @action="onAction"
        @paginate="onPaginate"
      />
    
    <!-- Confirm Dialogs -->
    <ConfirmDialog
      :show="showConfirmSend"
      :message="`Kirim ${selected.length} dokumen BPB?`"
      @confirm="confirmSend"
      @cancel="cancelSend"
    />
    <ConfirmDialog
      :show="showConfirmCancel"
      message="Batalkan dokumen BPB ini?"
      @confirm="confirmCancel"
      @cancel="cancelCancel"
    />
    </div>
  </div>
</template>