<template>
  <div class="bg-white rounded-xl p-4 shadow">
    <form @submit.prevent="onSubmit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs text-gray-500 mb-1">No. PO Anggaran</label>
          <select v-model="form.po_anggaran_id" class="select select-bordered w-full" @change="onPoChange">
            <option :value="''">Pilih PO Anggaran</option>
            <option v-for="opt in poOptions" :key="opt.id" :value="opt.id">{{ opt.no_po_anggaran }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Departemen</label>
          <DepartmentDropdown v-model="form.department_id" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Metode Pembayaran</label>
          <select v-model="form.metode_pembayaran" class="select select-bordered w-full">
            <option value="Transfer">Transfer</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Nama Bank</label>
          <select v-model="form.bank_id" class="select select-bordered w-full">
            <option :value="null">Pilih Bank</option>
            <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.nama_bank }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Nama Rekening</label>
          <input v-model="form.nama_rekening" class="input input-bordered w-full" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">No. Rekening/VA</label>
          <input v-model="form.no_rekening" class="input input-bordered w-full" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Total Anggaran</label>
          <input type="number" step="0.01" v-model.number="form.total_anggaran" class="input input-bordered w-full" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs text-gray-500 mb-1">Note</label>
          <textarea v-model="form.note" class="textarea textarea-bordered w-full"></textarea>
        </div>
      </div>

      <div class="mt-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Detail Pengeluaran</h3>
          <div class="flex gap-2">
            <button type="button" class="btn btn-sm" @click="clearItems">Clear (-)</button>
            <button type="button" class="btn btn-sm btn-primary" @click="showAdd = true">Tambah (+)</button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>Detail</th>
                <th>Keterangan</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Subtotal</th>
                <th>Realisasi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(it, idx) in form.items" :key="idx">
                <td>{{ it.jenis_pengeluaran_text }}</td>
                <td>{{ it.keterangan }}</td>
                <td>{{ it.harga }}</td>
                <td>{{ it.qty }}</td>
                <td>{{ it.satuan }}</td>
                <td>{{ (Number(it.harga)||0) * (Number(it.qty)||0) }}</td>
                <td>
                  <input type="number" step="0.01" class="input input-bordered input-sm w-28" v-model.number="it.realisasi" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex flex-wrap gap-4 justify-end text-sm">
          <div><span class="text-gray-500">Total Realisasi:</span> <span class="font-semibold">{{ totalRealisasi }}</span></div>
          <div><span class="text-gray-500">Sisa:</span> <span class="font-semibold">{{ sisa }}</span></div>
        </div>
      </div>

      <div class="mt-6 flex gap-2">
        <button type="button" class="btn" @click="goBack">Batal</button>
        <button type="button" class="btn" @click="saveDraft">Simpan Draft</button>
        <button type="button" class="btn btn-primary" @click="send">Kirim</button>
      </div>

      <dialog ref="modal" class="modal" :open="showAdd">
        <div class="modal-box">
          <h3 class="font-bold">Tambah Detail Pengeluaran</h3>
          <form @submit.prevent="addItem">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Jenis Pengeluaran</label>
                <input v-model="newItem.jenis_pengeluaran_text" class="input input-bordered w-full" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Keterangan</label>
                <input v-model="newItem.keterangan" class="input input-bordered w-full" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Harga</label>
                <input type="number" step="0.01" v-model.number="newItem.harga" class="input input-bordered w-full" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Qty</label>
                <input type="number" step="0.01" v-model.number="newItem.qty" class="input input-bordered w-full" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Satuan</label>
                <input v-model="newItem.satuan" class="input input-bordered w-full" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Realisasi</label>
                <input type="number" step="0.01" v-model.number="newItem.realisasi" class="input input-bordered w-full" />
              </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
              <button type="button" class="btn" @click="showAdd=false">Tutup</button>
              <button type="submit" class="btn btn-primary">Tambah</button>
            </div>
          </form>
        </div>
      </dialog>
    </form>
  </div>
</template>
<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DepartmentDropdown from '@/components/DepartmentDropdown.vue';
import axios from 'axios';

const props = defineProps<{ mode: 'create'|'edit'; realisasi?: any }>();
const form = reactive<any>({
  po_anggaran_id: props.realisasi?.po_anggaran_id ?? '',
  department_id: props.realisasi?.department_id ?? '',
  metode_pembayaran: props.realisasi?.metode_pembayaran ?? 'Transfer',
  bank_id: props.realisasi?.bank_id ?? null,
  nama_rekening: props.realisasi?.nama_rekening ?? '',
  no_rekening: props.realisasi?.no_rekening ?? '',
  total_anggaran: props.realisasi?.total_anggaran ?? 0,
  note: props.realisasi?.note ?? '',
  items: props.realisasi?.items ?? [],
});

const banks = ref<any[]>([]);
const poOptions = ref<any[]>([]);
const showAdd = ref(false);
const newItem = reactive<any>({ jenis_pengeluaran_id: null, jenis_pengeluaran_text: '', keterangan: '', harga: 0, qty: 0, satuan: '', realisasi: 0 });

async function loadBanks() { const { data } = await axios.get('/banks'); banks.value = data?.data ?? data ?? []; }
async function loadPoOptions() { const { data } = await axios.get('/realisasi/po-anggaran/options'); poOptions.value = data ?? []; }
loadBanks();
loadPoOptions();

const totalRealisasi = computed(() => (form.items || []).reduce((a: number, it: any) => a + (Number(it.realisasi)||0), 0));
const sisa = computed(() => Number(form.total_anggaran || 0) - Number(totalRealisasi.value || 0));

function goBack() { history.back(); }

function saveDraft() {
  if (props.mode === 'create') router.post('/realisasi', { ...form });
  else router.put(`/realisasi/${props.realisasi.id}`, { ...form });
}

function send() {
  if (props.mode === 'create') {
    router.post('/realisasi', { ...form });
  } else {
    router.put(`/realisasi/${props.realisasi.id}`, { ...form }, { onSuccess: () => router.post('/realisasi/send', { ids: [ props.realisasi.id ] }) });
  }
}

function clearItems() { form.items = []; }
function addItem() { form.items.push({ ...newItem }); showAdd.value = false; }

async function onPoChange() {
  if (!form.po_anggaran_id) return;
  const { data } = await axios.get(`/realisasi/po-anggaran/${form.po_anggaran_id}`);
  // Prefill fields from PO Anggaran
  form.department_id = data?.department_id ?? form.department_id;
  form.bank_id = data?.bank_id ?? form.bank_id;
  form.nama_rekening = data?.nama_rekening ?? form.nama_rekening;
  form.no_rekening = data?.no_rekening ?? form.no_rekening;
  form.total_anggaran = data?.nominal ?? form.total_anggaran;
  // Prefill items
  const items = (data?.items || []).map((it: any) => ({
    jenis_pengeluaran_id: it.jenis_pengeluaran_id ?? null,
    jenis_pengeluaran_text: it.jenis_pengeluaran_text ?? '',
    keterangan: it.keterangan ?? '',
    harga: it.harga ?? 0,
    qty: it.qty ?? 0,
    satuan: it.satuan ?? '',
    subtotal: (Number(it.harga)||0) * (Number(it.qty)||0),
    realisasi: 0,
  }));
  if (items.length) form.items = items;
}

function onSubmit() { saveDraft(); }
</script>
