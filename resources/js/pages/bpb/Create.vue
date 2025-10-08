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

    <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
      <button
        class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
        @click="saveDraft(true)"
      >
        <svg
          fill="#E6E6E6"
          height="24"
          viewBox="0 0 24 24"
          width="24"
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5"
        >
          <path
            d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
          />
        </svg>
        Kirim
      </button>
      <button
        class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
        @click="saveDraft(false)"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="w-5 h-5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"
          />
        </svg>
        Simpan Draft
      </button>
      <a
        href="/bpb"
        class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="w-5 h-5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
        Batal
      </a>
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


