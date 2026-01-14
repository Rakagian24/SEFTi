<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
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
        :creditCards="creditCards"
        :giroNumbers="giroNumbers"
        @close="goBack"
        @refreshTable="goBack"
        @created="handleCreated"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
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
  creditCards: any[];
}>();

const purchaseOrders = ref(props.purchaseOrders || []);
const banks = ref(props.banks || []);
const creditCards = ref(props.creditCards || []);
const giroNumbers = ref<any[]>([]); // tambahan

async function fetchGiroNumbers(search = "") {
  try {
    const { data } = await axios.get(route("memo-pembayaran.giro-numbers"), {
      params: { search, per_page: 100 },
    });
    giroNumbers.value = data.data;
  } catch (error) {
    console.error("Failed to fetch giro numbers:", error);
  }
}

onMounted(() => {
  fetchGiroNumbers();
});

function goBack() {
  router.visit("/memo-pembayaran");
}

function handleCreated() {
  addSuccess("Memo Pembayaran berhasil dibuat!");
  goBack();
}
</script>
