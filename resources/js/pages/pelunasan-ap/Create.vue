<template>
  <AppLayout>
    <template #header>
      <PageHeader title="Buat Pelunasan" />
    </template>

    <PelunasanApForm mode="create" />
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import PelunasanApForm from '@/components/pelunasan-ap/PelunasanApForm.vue'
</script>
