<script setup lang="ts">
import { computed, ref, watch } from "vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

const props = defineProps<{ data: any[] }>();
const emit = defineEmits<{
  select: [ids: number[]];
  action: [payload: { action: string; row: any }];
}>();

const selectedIds = ref<number[]>([]);
const showConfirm = ref(false);
const confirmId = ref<number | null>(null);

const hasSelectable = computed(() => props.data.some(r => r.status === 'Draft'));

const selectAll = computed({
  get() {
    const selectable = props.data.filter(r => r.status === 'Draft').map(r => r.id);
    return selectable.length > 0 && selectable.every(id => selectedIds.value.includes(id));
  },
  set(val: boolean) {
    if (!val) {
      selectedIds.value = [];
    } else {
      selectedIds.value = props.data.filter(r => r.status === 'Draft').map(r => r.id);
    }
    emit("select", selectedIds.value);
  }
});

watch(() => props.data, () => {
  const valid = new Set(props.data.filter(r => r.status === 'Draft').map(r => r.id));
  selectedIds.value = selectedIds.value.filter(id => valid.has(id));
  emit("select", selectedIds.value);
});

function onAction(action: string, row: any) {
  if (action === 'cancel') {
    confirmId.value = row.id;
    showConfirm.value = true;
  } else {
    emit('action', { action, row });
  }
}

function onConfirm() {
  if (confirmId.value != null) {
    const row = props.data.find(r => r.id === confirmId.value);
    if (row) emit('action', { action: 'cancel', row });
  }
  confirmId.value = null;
  showConfirm.value = false;
}

function onCancel() {
  confirmId.value = null;
  showConfirm.value = false;
}
</script>

<template>
  <div class="bg-white rounded-lg border shadow-sm overflow-x-auto">
    <table class="min-w-full">
      <thead>
        <tr class="bg-white border-b">
          <th class="px-6 py-3 text-center">
            <input type="checkbox" v-model="selectAll" :disabled="!hasSelectable" />
          </th>
          <th class="px-6 py-3 text-left">No. BPB</th>
          <th class="px-6 py-3 text-left">No. PO</th>
          <th class="px-6 py-3 text-left">No. PV</th>
          <th class="px-6 py-3 text-left">Tanggal</th>
          <th class="px-6 py-3 text-left">Status</th>
          <th class="px-6 py-3 text-left sticky right-0 bg-white">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr v-for="row in props.data" :key="row.id">
          <td class="px-6 py-3 text-center">
            <input
              type="checkbox"
              :disabled="row.status !== 'Draft'"
              :value="row.id"
              v-model="selectedIds"
              @change="emit('select', selectedIds)"
            />
          </td>
          <td class="px-6 py-3 whitespace-nowrap">{{ row.no_bpb || '-' }}</td>
          <td class="px-6 py-3 whitespace-nowrap">{{ row.purchase_order?.no_po || '-' }}</td>
          <td class="px-6 py-3 whitespace-nowrap">{{ row.payment_voucher?.no_pv || '-' }}</td>
          <td class="px-6 py-3 whitespace-nowrap">{{ row.tanggal || '-' }}</td>
          <td class="px-6 py-3">
            <span class="px-2 py-1 rounded text-xs"
              :class="{
                'bg-gray-100 text-gray-700': row.status==='Draft',
                'bg-yellow-100 text-yellow-700': row.status==='In Progress',
                'bg-green-100 text-green-700': row.status==='Approved',
                'bg-red-100 text-red-700': row.status==='Canceled' || row.status==='Rejected'
              }"
            >{{ row.status }}</span>
          </td>
          <td class="px-6 py-3 whitespace-nowrap sticky right-0 bg-white">
            <div class="flex gap-3 text-[#5856D6]">
              <button title="Edit" :disabled="row.status!=='Draft'" @click="onAction('edit', row)">✎</button>
              <button title="Cancel" :disabled="row.status!=='Draft'" @click="onAction('cancel', row)">🗑</button>
              <button title="Detail" @click="onAction('detail', row)">🔍</button>
              <button title="Download" :disabled="row.status==='Canceled'" @click="onAction('download', row)">⬇</button>
              <button title="Log" @click="onAction('log', row)">📝</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <ConfirmDialog
      v-if="showConfirm"
      :show="showConfirm"
      :message="'Apakah Anda yakin ingin membatalkan dokumen ini?'"
      @confirm="onConfirm"
      @cancel="onCancel"
    />
  </div>
</template>


