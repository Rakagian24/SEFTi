<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";

defineOptions({ layout: AppLayout });

const { addSuccess, addError, addWarning, addInfo } = useMessagePanel();

function testSuccess() {
  addSuccess('Ini adalah pesan sukses!', 'Berhasil', 3000);
}

function testError() {
  addError('Ini adalah pesan error!', 'Error', 5000);
}

function testWarning() {
  addWarning('Ini adalah pesan warning!', 'Peringatan', 4000);
}

function testInfo() {
  addInfo('Ini adalah pesan informasi!', 'Informasi', 6000);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Test Message Panel</h1>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button
          @click="testSuccess"
          class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
        >
          Test Success
        </button>

        <button
          @click="testError"
          class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
        >
          Test Error
        </button>

        <button
          @click="testWarning"
          class="px-6 py-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
        >
          Test Warning
        </button>

        <button
          @click="testInfo"
          class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
        >
          Test Info
        </button>
      </div>

      <div class="mt-8 p-6 bg-white rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Fitur Message Panel:</h2>
        <ul class="space-y-2 text-gray-700">
          <li>• Pesan otomatis hilang setelah 5 detik (default)</li>
          <li>• Bisa ditutup manual dengan tombol X</li>
          <li>• Mendukung 4 tipe: Success, Error, Warning, Info</li>
          <li>• Animasi smooth saat muncul dan hilang</li>
          <li>• Posisi bisa diatur (top-right, top-left, dll)</li>
          <li>• Tombol "Clear All" jika ada multiple pesan</li>
        </ul>
      </div>
    </div>
  </div>
</template>
