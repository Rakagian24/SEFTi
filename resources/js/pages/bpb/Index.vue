<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import BpbFilter from "@/components/bpb/BpbFilter.vue";
import BpbTable from "@/components/bpb/BpbTable.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";

const rows = ref<any[]>([]);
const meta = ref<any>({});
const selected = ref<number[]>([]);
const loading = ref(false);
const { addSuccess, addError, clearAll } = useMessagePanel();

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

function onSend() {
  if (selected.value.length === 0) return;
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
    }
  );
}

function onAction(e: { action: string; row: any }) {
  const { action, row } = e;
  if (action === "edit") {
    router.visit(`/bpb/${row.id}/edit`);
  } else if (action === "cancel") {
    clearAll();
    router.post(`/bpb/${row.id}/cancel`, {}, {
      onSuccess: () => {
        addSuccess("Dokumen dibatalkan");
        fetchData({});
      },
      onError: (err: any) => addError((err && (Object.values(err).flat() as any).join(" ")) || "Gagal membatalkan dokumen"),
    });
  } else if (action === "detail") {
    router.visit(`/bpb/${row.id}/detail`);
  } else if (action === "download") {
    window.open(`/bpb/${row.id}/download`, "_blank");
  } else if (action === "log") {
    router.visit(`/bpb/${row.id}/log`);
  }
}

onMounted(() => fetchData({}));
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Bukti Penerimaan Barang</h1>
      <div class="flex gap-2">
        <button
          class="px-3 py-2 rounded bg-[#5856D6] text-white disabled:opacity-50"
          :disabled="selected.length === 0"
          @click="onSend"
        >
          Kirim
        </button>
        <a href="/bpb/create" class="px-3 py-2 rounded bg-black text-white">Add New</a>
      </div>
    </div>

    <BpbFilter @filter="onFilter" @reset="onReset" />

    <BpbTable :data="rows" @select="(ids: number[]) => selected = ids" @action="onAction" />

    <div class="flex items-center justify-between text-sm text-gray-600">
      <div>Showing {{ rows.length }} of {{ meta.total || 0 }}</div>
      <div class="flex gap-2">
        <button
          class="px-2 py-1 border rounded"
          :disabled="!meta.prev_page_url"
          @click="fetchData({ page: (meta.current_page || 1) - 1 })"
        >
          Prev
        </button>
        <button
          class="px-2 py-1 border rounded"
          :disabled="!meta.next_page_url"
          @click="fetchData({ page: (meta.current_page || 1) + 1 })"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>


