<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PengeluaranBarangForm from '@/components/pengeluaran-barang/PengeluaranBarangForm.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';

defineOptions({ layout: AppLayout });

const { departments, jenisPengeluaran, userDepartments } = defineProps<{
  departments: { id: number; name: string }[];
  jenisPengeluaran: { id: string; name: string }[];
  userDepartments: { id: number; name: string }[];
}>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Pengeluaran Barang', href: '/pengeluaran-barang' },
  { label: 'Create' }
];

// Global message panel integration
const page = usePage();
const { addSuccess, addError } = useMessagePanel();

watch(
  () => page.props,
  (newProps: any) => {
    const flash = newProps?.flash || {};
    if (typeof flash.success === 'string' && flash.success) addSuccess(flash.success);
    if (typeof flash.error === 'string' && flash.error) addError(flash.error);
  },
  { immediate: true }
);
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Pengeluaran Barang</h1>
        <p class="text-gray-600 mt-1">Buat dokumen pengeluaran barang baru</p>
      </div>

      <!-- Form -->
      <PengeluaranBarangForm
        :departments="departments"
        :jenis-pengeluaran="jenisPengeluaran"
        :user-departments="userDepartments"
      />
    </div>
  </div>
</template>
