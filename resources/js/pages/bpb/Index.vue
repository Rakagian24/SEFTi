<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import BpbFilter from "@/components/bpb/BpbFilter.vue";
import BpbTable from "@/components/bpb/BpbTable.vue";

const rows = ref<any[]>([]);
const meta = ref<any>({});
const selected = ref<number[]>([]);
const loading = ref(false);

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
  router.post(
    "/bpb/send",
    { ids: selected.value },
    { preserveScroll: true, onSuccess: () => fetchData({}) }
  );
}

function onAction(e: { action: string; row: any }) {
  const { action, row } = e;
  if (action === "edit") {
    // navigate to edit form if added later
  } else if (action === "cancel") {
    router.post(`/bpb/${row.id}/cancel`, {}, { onSuccess: () => fetchData({}) });
  } else if (action === "detail") {
    // show modal/detail later
  } else if (action === "download") {
    window.open(`/bpb/${row.id}/download`, "_blank");
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


