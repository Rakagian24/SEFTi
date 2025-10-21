<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Detail Realisasi" />

      <div class="bg-white rounded-xl p-4 shadow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div><span class="text-gray-500">No. Realisasi:</span> <span class="font-semibold">{{ realisasi.no_realisasi || '-' }}</span></div>
          <div><span class="text-gray-500">Tanggal:</span> <span class="font-semibold">{{ realisasi.tanggal || '-' }}</span></div>
          <div><span class="text-gray-500">No. PO Anggaran:</span> <span class="font-semibold">{{ realisasi.poAnggaran?.no_po_anggaran || '-' }}</span></div>
          <div><span class="text-gray-500">Departemen:</span> <span class="font-semibold">{{ realisasi.department?.name || '-' }}</span></div>
          <div><span class="text-gray-500">Metode Bayar:</span> <span class="font-semibold">{{ realisasi.metode_pembayaran }}</span></div>
          <div><span class="text-gray-500">Bank:</span> <span class="font-semibold">{{ realisasi.bank?.nama_bank || '-' }}</span></div>
          <div><span class="text-gray-500">Nama Rekening:</span> <span class="font-semibold">{{ realisasi.nama_rekening }}</span></div>
          <div><span class="text-gray-500">No. Rekening/VA:</span> <span class="font-semibold">{{ realisasi.no_rekening }}</span></div>
          <div><span class="text-gray-500">Total Anggaran:</span> <span class="font-semibold">{{ realisasi.total_anggaran }}</span></div>
          <div><span class="text-gray-500">Total Realisasi:</span> <span class="font-semibold">{{ realisasi.total_realisasi }}</span></div>
          <div class="md:col-span-2"><span class="text-gray-500">Note:</span> <span class="font-semibold">{{ realisasi.note || '-' }}</span></div>
          <div><span class="text-gray-500">Status:</span> <span class="font-semibold">{{ realisasi.status }}</span></div>
        </div>

        <div class="mt-6 overflow-x-auto">
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
              <tr v-for="(it, idx) in realisasi.items || []" :key="idx">
                <td>{{ it.jenis_pengeluaran_text }}</td>
                <td>{{ it.keterangan }}</td>
                <td>{{ it.harga }}</td>
                <td>{{ it.qty }}</td>
                <td>{{ it.satuan }}</td>
                <td>{{ it.subtotal }}</td>
                <td>{{ it.realisasi }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex gap-2">
          <button class="btn" @click="goBack">Kembali</button>
          <button class="btn" :disabled="realisasi.status==='Canceled'" @click="downloadPdf">Unduh PDF</button>
          <button class="btn" @click="goLog">Lihat Log</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any }>();
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Detail' }];

function goBack() { history.back(); }
function goLog() { router.visit(`/realisasi/${props.realisasi.id}/log`); }
function downloadPdf() { window.open(`/realisasi/${props.realisasi.id}/download`, '_blank'); }
</script>
