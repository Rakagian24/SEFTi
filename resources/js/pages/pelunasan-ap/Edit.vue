<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Pelunasan AP</h1>
        <p class="text-gray-600 mt-1">Edit dokumen pelunasan AP</p>
      </div>

      <PelunasanApForm mode="edit" :pelunasan-ap="pelunasanAp" :departments="departments" />
    </div>
  </div>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue'
import PelunasanApForm from '@/components/pelunasan-ap/PelunasanApForm.vue'

defineOptions({ layout: AppLayout })

const { pelunasanAp, departments } = defineProps<{
  pelunasanAp: any
  suppliers: any[]
  departments: any[]
  bankKeluars: any[]
  bankMutasis: any[]
}>()

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Pelunasan AP', href: '/pelunasan-ap' },
  { label: 'Edit Pelunasan' },
]
</script>
