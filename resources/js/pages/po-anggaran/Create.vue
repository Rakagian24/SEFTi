<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Create PO Anggaran" />
      <PoAnggaranForm mode="create" :departments="departments" />
    </div>
  </div>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import PoAnggaranForm from '@/components/po-anggaran/PoAnggaranForm.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ departments?: any[] }>();
const departments = props.departments || [];
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'PO Anggaran', href: '/po-anggaran' }, { label: 'Create' }];
</script>
