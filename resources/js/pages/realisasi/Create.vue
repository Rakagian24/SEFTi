<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Realisasi" :show-add-button="false" />
      <RealisasiForm mode="create" :departments="departments" />
    </div>
  </div>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import RealisasiForm from '@/components/realisasi/RealisasiForm.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ departments: any[] }>();
const departments = props.departments || [];
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Create' }];
</script>
