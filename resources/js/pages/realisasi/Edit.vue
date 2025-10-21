<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Edit Realisasi" />
      <RealisasiForm mode="edit" :realisasi="realisasi" />
    </div>
  </div>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import RealisasiForm from '@/components/realisasi/RealisasiForm.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any }>();
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Edit' }];
</script>
