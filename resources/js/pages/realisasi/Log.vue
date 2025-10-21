<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Log Activity Realisasi" />
      <div class="bg-white rounded-xl p-4 shadow">
        <pre class="text-xs">{{ JSON.stringify(logs, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any; logs: any[] }>();
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Log' }];
</script>
