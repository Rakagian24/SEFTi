<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <SquareCheck class="w-4 h-4 mr-1" />
            Dokumen yang menunggu persetujuan
          </div>
        </div>
      </div>

      <!-- Statistics Cards Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Purchase Order Card -->
        <ApprovalCard
          v-if="canAccess('purchase_order')"
          title="Purchase Order"
          :count="purchaseOrderCount"
          icon="credit-card"
          color="blue"
          href="/approval/purchase-orders"
          :loading="loading.purchaseOrder"
        />

        <!-- Payment Voucher Card -->
        <!-- <ApprovalCard
          v-if="canAccess('payment_voucher')"
          title="Payment Voucher"
          :count="paymentVoucherCount"
          icon="credit-card"
          color="green"
          href="/approval/payment-vouchers"
          :loading="loading.paymentVoucher"
        /> -->

        <!-- Anggaran Card -->
        <!-- <ApprovalCard
          v-if="canAccess('anggaran')"
          title="Anggaran"
          :count="anggaranCount"
          icon="pie-chart"
          color="purple"
          href="/approval/anggaran"
          :loading="loading.anggaran"
        /> -->

        <!-- Realisasi Card -->
        <!-- <ApprovalCard
          v-if="canAccess('realisasi')"
          title="Realisasi"
          :count="realisasiCount"
          icon="trending-up"
          color="orange"
          href="/approval/realisasi"
          :loading="loading.realisasi"
        /> -->

        <!-- Pelunasan Card -->
        <!-- <ApprovalCard
          v-if="canAccess('pelunasan')"
          title="Pelunasan"
          :count="pelunasanCount"
          icon="file-text"
          color="red"
          href="/approval/pelunasan"
          :loading="loading.pelunasan"
        /> -->

        <!-- BPB Card -->
        <!-- <ApprovalCard
          v-if="canAccess('bpb')"
          title="BPB"
          :count="bpbCount"
          icon="package"
          color="indigo"
          href="/approval/bpb"
          :loading="loading.bpb"
          :is-selected="true"
        /> -->

        <!-- Memo Pembayaran Card -->
        <ApprovalCard
          v-if="canAccess('memo_pembayaran')"
          title="Memo Pembayaran"
          :count="memoPembayaranCount"
          icon="file-text"
          color="teal"
          href="/approval/memo-pembayaran"
          :loading="loading.memoPembayaran"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import ApprovalCard from "@/components/approval/ApprovalCard.vue";
import { useApi } from "@/composables/useApi";
import { SquareCheck } from "lucide-vue-next";

defineOptions({ layout: AppLayout });

// Get user role and permissions from Inertia props
const props = defineProps<{
  userRole: string;
  userPermissions: string[];
}>();

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Approval" }];

// Initialize API composable
const { get } = useApi();

// Test authentication status
const testAuthentication = async () => {
  try {
    const response = await get("/test-auth-status");
    return response.authenticated;
  } catch (error) {
    console.error("Authentication test failed:", error);
    return false;
  }
};

// Loading states
const loading = ref({
  purchaseOrder: false,
  paymentVoucher: false,
  anggaran: false,
  realisasi: false,
  bpb: false,
  memoPembayaran: false,
  pelunasan: false,
});

// Document counts
const purchaseOrderCount = ref(0);
// const paymentVoucherCount = ref(15);
// const anggaranCount = ref(12);
// const realisasiCount = ref(10);
// const bpbCount = ref(6);
const memoPembayaranCount = ref(0);
// const pelunasanCount = ref(5);

// Check if user can access specific document type based on role
const canAccess = (documentType: string): boolean => {
  const role = props.userRole;

  switch (role) {
    case "Kepala Toko":
      return ["purchase_order", "anggaran", "memo_pembayaran"].includes(documentType);

    case "Kabag":
      return [
        "purchase_order",
        "payment_voucher",
        "anggaran",
        "bpb",
        "realisasi",
        "memo_pembayaran",
        "pelunasan",
      ].includes(documentType);

    case "Staff Akunting & Finance":
      return ["realisasi"].includes(documentType);

    case "Kadiv":
      return [
        "purchase_order",
        "payment_voucher",
        "anggaran",
        "memo_pembayaran",
      ].includes(documentType);

    case "Direksi":
      return ["purchase_order", "anggaran", "payment_voucher", "realisasi"].includes(
        documentType
      );

    default:
      // For demo purposes, show all cards
      return true;
  }
};

// Fetch document counts
const fetchDocumentCounts = async () => {
  try {
    // Test authentication first
    const isAuth = await testAuthentication();
    if (!isAuth) {
      console.warn("User not authenticated, using fallback values");
      purchaseOrderCount.value = 8;
      return;
    }

    // Fetch Purchase Order count (only active document type)
    if (canAccess("purchase_order")) {
      loading.value.purchaseOrder = true;
      try {
        const data = await get("/api/approval/purchase-orders/count");
        purchaseOrderCount.value = data.count;
      } catch (error) {
        console.error("Error fetching purchase order count:", error);
        if (error instanceof Error && error.message.includes("401")) {
          console.warn(
            "Authentication failed for purchase order count, using fallback value"
          );
          // Try the test route to get more debug info
          try {
            const testData = await get("/test-api-approval-count");

            purchaseOrderCount.value = testData.count || 8;
          } catch (testError) {
            console.error("Test route also failed:", testError);
            purchaseOrderCount.value = 8; // Fallback value for development
          }
        } else {
          purchaseOrderCount.value = 0;
        }
      } finally {
        loading.value.purchaseOrder = false;
      }
    } else {
      // If user doesn't have access, set count to 0
      purchaseOrderCount.value = 0;
    }

    // Fetch Memo Pembayaran count
    if (canAccess("memo_pembayaran")) {
      loading.value.memoPembayaran = true;
      try {
        const data = await get("/api/approval/memo-pembayaran/count");
        memoPembayaranCount.value = data.count;
      } catch (error) {
        console.error("Error fetching memo pembayaran count:", error);
        if (error instanceof Error && error.message.includes("401")) {
          console.warn(
            "Authentication failed for memo pembayaran count, using fallback value"
          );
          memoPembayaranCount.value = 5; // Fallback value for development
        } else {
          memoPembayaranCount.value = 0;
        }
      } finally {
        loading.value.memoPembayaran = false;
      }
    } else {
      // If user doesn't have access, set count to 0
      memoPembayaranCount.value = 0;
    }

    // Note: Other document types (payment_voucher, anggaran, realisasi, bpb, pelunasan)
    // are currently commented out in the template, so we don't fetch their counts
    // When they are uncommented, add their respective API endpoints and count fetching logic here
  } catch (error) {
    console.error("Error fetching document counts:", error);
    // Set fallback values for all counts
    purchaseOrderCount.value = 8;
    memoPembayaranCount.value = 5;
  }
};

// Initialize and fetch counts
onMounted(async () => {
  // User role and permissions are now passed as props from the backend
  // No need to make API calls for authentication data

  // Try to fetch data, but don't fail if authentication is not working
  try {
    await fetchDocumentCounts();
  } catch (error) {
    console.error("Failed to fetch some data, using fallback values:", error);
    // Set fallback values
    purchaseOrderCount.value = 8;
    memoPembayaranCount.value = 5;
  }
});
</script>
