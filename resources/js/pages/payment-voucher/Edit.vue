<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Payment Voucher</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Edit dokumen Payment Voucher
          </div>
        </div>
      </div>

      <PaymentVoucherForm
        v-model="paymentVoucher"
        :supplierOptions="purchaseOrders"
        :departmentOptions="[]"
        :perihalOptions="[]"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import PaymentVoucherForm from "../../components/payment-voucher/PaymentVoucherForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-voucher" },
  { label: "Edit" },
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  paymentVoucher: any;
  purchaseOrders: any[];
  banks: any[];
}>();

const paymentVoucher = ref(props.paymentVoucher);
const purchaseOrders = ref(props.purchaseOrders || []);
</script>
