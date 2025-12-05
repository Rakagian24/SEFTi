<template>
  <AppLayout>
    <template #header>
      <PageHeader title="Edit Pelunasan" />
    </template>

    <PelunasanApForm mode="edit" :pelunasan-ap="pelunasanAp" />
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import PelunasanApForm from '@/components/pelunasan-ap/PelunasanApForm.vue'

const { pelunasanAp } = defineProps<{ pelunasanAp: any }>()
</script>
