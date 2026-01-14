<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Buat Pelunasan AP</h1>
        <p class="text-gray-600 mt-1">Buat dokumen pelunasan AP baru</p>
      </div>

      <PelunasanApForm mode="create" :departments="departments" />
    </div>
  </div>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue'
import PelunasanApForm from '@/components/pelunasan-ap/PelunasanApForm.vue'

defineOptions({ layout: AppLayout })

const { departments } = defineProps<{
  suppliers: any[]
  departments: any[]
  bankKeluars: any[]
  bankMutasis: any[]
}>()

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Pelunasan AP', href: '/pelunasan-ap' },
  { label: 'Buat Pelunasan' },
]
</script>
