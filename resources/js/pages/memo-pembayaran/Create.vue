<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Memo Pembayaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Buat dokumen Memo Pembayaran baru
          </div>
        </div>
      </div>

      <MemoPembayaranForm
        :purchaseOrders="purchaseOrders"
        :banks="banks"
        @close="goBack"
        @refreshTable="goBack"
        @created="handleCreated"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import MemoPembayaranForm from "../../components/memo-pembayaran/MemoPembayaranForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Memo Pembayaran", href: "/memo-pembayaran" },
  { label: "Buat Baru" },
];

const { addSuccess } = useMessagePanel();

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrders: any[];
  banks: any[];
}>();

const purchaseOrders = ref(props.purchaseOrders || []);
const banks = ref(props.banks || []);

function goBack() {
  router.visit("/memo-pembayaran");
}

function handleCreated() {
  addSuccess("Memo Pembayaran berhasil dibuat!");
  goBack();
}
</script>
