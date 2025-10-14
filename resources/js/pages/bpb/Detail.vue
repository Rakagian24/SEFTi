<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any }>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: props.bpb?.no_bpb || `BPB #${props.bpb?.id}` },
];
</script>

<template>
  <div class="space-y-6">
    <Breadcrumbs :items="breadcrumbs" />

    <div class="bg-white rounded-lg border p-4 space-y-4">
      <h1 class="text-xl font-semibold">Detail BPB</h1>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div><span class="text-gray-500">No. BPB</span><div class="font-medium">{{ props.bpb?.no_bpb || '-' }}</div></div>
        <div><span class="text-gray-500">Tanggal</span><div class="font-medium">{{ props.bpb?.tanggal || '-' }}</div></div>
        <div><span class="text-gray-500">Departemen</span><div class="font-medium">{{ props.bpb?.department?.name || '-' }}</div></div>
        <div><span class="text-gray-500">Supplier</span><div class="font-medium">{{ props.bpb?.supplier?.nama_supplier || '-' }}</div></div>
        <div><span class="text-gray-500">No. PO</span><div class="font-medium">{{ props.bpb?.purchase_order?.no_po || '-' }}</div></div>
        <div><span class="text-gray-500">No. PV</span><div class="font-medium">{{ props.bpb?.payment_voucher?.no_pv || '-' }}</div></div>
        <div><span class="text-gray-500">Status</span>
          <div class="font-medium">
            <span class="px-2 py-1 rounded text-xs"
              :class="{
                'bg-gray-100 text-gray-700': props.bpb?.status==='Draft',
                'bg-yellow-100 text-yellow-700': props.bpb?.status==='In Progress',
                'bg-green-100 text-green-700': props.bpb?.status==='Approved',
                'bg-red-100 text-red-700': props.bpb?.status==='Canceled' || props.bpb?.status==='Rejected'
              }"
            >{{ props.bpb?.status }}</span>
          </div>
        </div>
      </div>

      <div class="space-y-2">
        <div class="text-sm text-gray-500">Keterangan</div>
        <div class="text-sm">{{ props.bpb?.keterangan || '-' }}</div>
      </div>
    </div>

    <div class="bg-white rounded-lg border p-4">
      <h2 class="font-semibold mb-3">Barang</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-3 py-2 text-left">Nama Barang</th>
              <th class="px-3 py-2 text-right">Qty</th>
              <th class="px-3 py-2 text-left">Satuan</th>
              <th class="px-3 py-2 text-right">Harga</th>
              <th class="px-3 py-2 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(it, idx) in props.bpb?.items || []" :key="idx" class="border-t">
              <td class="px-3 py-2">{{ it.nama_barang }}</td>
              <td class="px-3 py-2 text-right">{{ it.qty }}</td>
              <td class="px-3 py-2">{{ it.satuan }}</td>
              <td class="px-3 py-2 text-right">{{ Number(it.harga).toLocaleString() }}</td>
              <td class="px-3 py-2 text-right">{{ (Number(it.qty)*Number(it.harga)).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 ml-auto w-72 space-y-1 text-sm">
        <div class="flex justify-between"><span>Total</span><span>{{ Number(props.bpb?.subtotal||0).toLocaleString() }}</span></div>
        <div class="flex justify-between"><span>Diskon</span><span>{{ Number(props.bpb?.diskon||0).toLocaleString() }}</span></div>
        <div class="flex justify-between"><span>DPP</span><span>{{ Number(props.bpb?.dpp||0).toLocaleString() }}</span></div>
        <div class="flex justify-between"><span>PPN</span><span>{{ Number(props.bpb?.ppn||0).toLocaleString() }}</span></div>
        <div class="flex justify-between"><span>PPH</span><span>{{ Number(props.bpb?.pph||0).toLocaleString() }}</span></div>
        <div class="border-t pt-2 flex justify-between font-semibold"><span>Grand Total</span><span>{{ Number(props.bpb?.grand_total||0).toLocaleString() }}</span></div>
      </div>
    </div>
  </div>
</template>
