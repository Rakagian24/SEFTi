<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import BpbForm from '@/components/bpb/BpbForm.vue';
import BpbItemsTable from '@/components/bpb/BpbItemsTable.vue';
import PoPickerModal from '@/components/bpb/PoPickerModal.vue';
import PphModal from '@/components/bpb/PphModal.vue';

const page = usePage();
const latestPOs = (page.props as any).latestPOs || [];
const suppliers = (page.props as any).suppliers || [];

type Item = { nama_barang: string; qty: number; satuan: string; harga: number };

const form = ref({
  department_id: (page.props as any).auth?.user?.department_id || null,
  purchase_order_id: '',
  supplier_id: '',
  alamat: '',
  note: '',
  keterangan: '',
  items: [] as Item[],
  diskon: 0,
  use_ppn: false,
  ppn_rate: 11,
  use_pph: false,
  pph_rate: 0,
});

function addItem() {
  tempItem.value = { nama_barang: '', qty: 1, satuan: '', harga: 0 };
  showItemModal.value = true;
}
function clearItems() { form.value.items = []; }

const showItemModal = ref(false);
const tempItem = ref<Item>({ nama_barang: '', qty: 1, satuan: '', harga: 0 });
const showPoModal = ref(false);
const showPphModal = ref(false);
function confirmItem() {
  if (!tempItem.value.nama_barang || !tempItem.value.qty || !tempItem.value.satuan || !tempItem.value.harga) return;
  form.value.items.push({ ...tempItem.value });
  showItemModal.value = false;
}

function saveDraft(send = false) {
  if (!form.value.purchase_order_id || !form.value.supplier_id || !form.value.alamat) return;
  router.post('/bpb/store-draft', form.value, {
    onSuccess: () => {
      if (send) router.post('/bpb/send', { ids: [(router as any).page?.props?.bpb?.id].filter(Boolean) });
    }
  });
}

function savePph(payload: { kode: string; nama: string; tarif: number; deskripsi?: string }) {
  // call backend endpoint that already exists for PO: purchase-orders/add-pph
  axios
    .post('/purchase-orders/add-pph', {
      kode_pph: payload.kode,
      nama_pph: payload.nama,
      tarif_pph: payload.tarif,
      deskripsi: payload.deskripsi || '',
      status: 'active',
    })
    .then(() => {
      // set current form pph_rate from newly added master
      form.value.use_pph = true;
      form.value.pph_rate = payload.tarif;
      showPphModal.value = false;
    })
    .catch(() => {
      showPphModal.value = false;
    });
}

</script>

<template>
  <div class="space-y-4">
    <h1 class="text-xl font-semibold">Create Bukti Penerimaan Barang</h1>

    <BpbForm v-model="form" :latestPOs="latestPOs" :suppliers="suppliers" />

    <BpbItemsTable v-model="form" @add-item="addItem" @clear-items="clearItems" @add-pph="() => (showPphModal = true)" />

    <div class="flex gap-2">
      <button class="px-3 py-2 rounded bg-[#5856D6] text-white" @click="saveDraft(true)">Kirim</button>
      <button class="px-3 py-2 rounded bg-blue-600 text-white" @click="saveDraft(false)">Simpan Draft</button>
      <a href="/bpb" class="px-3 py-2 rounded border">Batal</a>
    </div>

    <!-- Modal Tambah Item -->
    <div v-if="showItemModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
      <div class="bg-white p-4 rounded w-[420px] space-y-3">
        <h3 class="font-semibold">Tambah Barang</h3>
        <div class="space-y-1">
          <label class="text-xs text-gray-500">Nama Barang</label>
          <input v-model="tempItem.nama_barang" class="border rounded px-2 py-1 w-full" />
        </div>
        <div class="flex gap-2">
          <div class="space-y-1">
            <label class="text-xs text-gray-500">Qty</label>
            <input type="number" v-model.number="tempItem.qty" class="border rounded px-2 py-1 w-28" />
          </div>
          <div class="space-y-1">
            <label class="text-xs text-gray-500">Satuan</label>
            <input v-model="tempItem.satuan" class="border rounded px-2 py-1 w-28" />
          </div>
          <div class="space-y-1">
            <label class="text-xs text-gray-500">Harga</label>
            <input type="number" v-model.number="tempItem.harga" class="border rounded px-2 py-1 w-32" />
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button class="px-3 py-1 rounded border" @click="showItemModal=false">Batal</button>
          <button class="px-3 py-1 rounded bg-[#5856D6] text-white" @click="confirmItem">Tambah</button>
        </div>
      </div>
    </div>

    <!-- Modal Picker PO (contoh data menggunakan latestPOs; bisa diganti sumber API) -->
    <PoPickerModal :show="showPoModal" :data="latestPOs" @close="showPoModal=false" @pick="(ids:any)=>{ if(ids && ids.length){ form.purchase_order_id = ids[0]; } showPoModal=false; }" />

    <!-- Modal Tambah PPh (hook simpan ke master nanti) -->
    <PphModal :show="showPphModal" @close="showPphModal=false" @save="savePph" />
  </div>
</template>


