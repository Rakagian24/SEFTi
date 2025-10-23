<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <LogScaffold
        :breadcrumbs="breadcrumbs"
        headerTitle="Realisasi Activity Details"
        infoTitle="Realisasi Activities"
        :infoSubtitle="`Riwayat aktivitas untuk Realisasi #${ props.realisasi?.id ?? '' }`"
      >
        <div class="bg-white rounded-xl p-4 shadow">
          <pre class="text-xs">{{ JSON.stringify(logs, null, 2) }}</pre>
        </div>
      </LogScaffold>
    </div>
  </div>
  </template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import LogScaffold from '@/components/logs/LogScaffold.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any; logs: any[] }>();
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Log' }];
</script>
