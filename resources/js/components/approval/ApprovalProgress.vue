<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Progress Approval</h3>

    <!-- Progress Steps -->
    <div class="space-y-3">
      <div
        v-for="(step, index) in displayProgress"
        :key="step.step"
        class="relative flex items-center gap-3"
      >
        <!-- Step Icon -->
        <div
          class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          :class="getStepIconClass(step.status)"
        >
          <CheckIcon v-if="step.status === 'completed'" class="w-4 h-4" />
          <ClockIcon v-else-if="step.status === 'current'" class="w-4 h-4" />
          <XIcon v-else-if="step.status === 'rejected'" class="w-4 h-4" />
          <span v-else>{{ getPendingIconText(index) }}</span>
        </div>

        <!-- Step Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-900">
                {{ getStepLabel(step.step) }}
              </p>
              <p class="text-xs text-gray-500">
                {{ step.role }}
              </p>
            </div>

            <!-- Status Badge -->
            <div
              class="px-2 py-1 text-xs font-medium rounded-full self-center"
              :class="getStatusBadgeClass(step.status)"
            >
              {{ getStatusLabel(step.status) }}
            </div>
          </div>

          <!-- Completion Info -->
          <div v-if="step.status === 'completed' && step.completed_at" class="mt-1">
            <p class="text-xs text-gray-500">
              {{ getCompletionText(step.step) }} oleh {{ step.completed_by?.name }} pada
              {{ formatDateTime(step.completed_at) }}
            </p>
          </div>
        </div>

        <!-- Connector Line -->
        <!-- <div
          v-if="index < displayProgress.length - 1"
          class="absolute left-4 top-8 w-0.5 h-6 bg-gray-200"
        ></div> -->
      </div>
    </div>

    <!-- Current Action Buttons -->
    <div v-if="shouldShowActionButtons" class="mt-6 pt-4 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-900">
            {{
              props.userRole === "Admin"
                ? "Tindakan Admin:"
                : "Tindakan yang dapat dilakukan:"
            }}
          </p>
          <p class="text-xs text-gray-500">
            {{ getCurrentActionDescription() }}
          </p>
        </div>

        <div class="flex space-x-2">
          <button
            v-if="canVerify"
            @click="$emit('verify', purchaseOrder)"
            :class="
              'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md ' +
              getApprovalButtonClassForTemplate('verify')
            "
          >
            <CheckIcon class="w-4 h-4 mr-1" />
            Verifikasi
          </button>

          <button
            v-if="canValidate"
            @click="$emit('validate', purchaseOrder)"
            :class="
              'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md ' +
              getApprovalButtonClassForTemplate('validate')
            "
          >
            <CheckIcon class="w-4 h-4 mr-1" />
            Validasi
          </button>

          <button
            v-if="canApprove"
            @click="$emit('approve', purchaseOrder)"
            :class="
              'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md ' +
              getApprovalButtonClassForTemplate('approve')
            "
          >
            <CheckIcon class="w-4 h-4 mr-1" />
            Setujui
          </button>

          <button
            v-if="canReject"
            @click="$emit('reject', purchaseOrder)"
            class="inline-flex items-center px-3 py-2 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            <XIcon class="w-4 h-4 mr-1" />
            Tolak
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { CheckIcon, ClockIcon, XIcon } from "lucide-vue-next";
import { getApprovalButtonClass } from "@/lib/status";

function getApprovalButtonClassForTemplate(action: string) {
  return getApprovalButtonClass(action);
}

interface ProgressStep {
  step: string;
  role: string;
  status: "pending" | "current" | "completed" | "rejected";
  completed_at?: string;
  completed_by?: {
    id: number;
    name: string;
  };
}

interface Props {
  progress: ProgressStep[];
  purchaseOrder: any;
  userRole: string;
  canVerify?: boolean;
  canValidate?: boolean;
  canApprove?: boolean;
  canReject?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  canVerify: false,
  canValidate: false,
  canApprove: false,
  canReject: false,
});

defineEmits<{
  verify: [purchaseOrder: any];
  validate: [purchaseOrder: any];
  approve: [purchaseOrder: any];
  reject: [purchaseOrder: any];
}>();

// const canPerformAction = computed(() => {
//   return props.canVerify || props.canValidate || props.canApprove || props.canReject;
// });

// const hasAvailableAction = computed(() => {
//   return (
//     props.canVerify ||
//     props.canValidate ||
//     props.canApprove ||
//     (props.canReject && currentStep.value?.status === "current")
//   );
// });

// Normalize progress for display. If document is rejected,
// mark only steps with completed_at as completed, others pending.
const displayProgress = computed<ProgressStep[]>(() => {
  const steps = props.progress || [];
  if (props.purchaseOrder?.status === "Rejected") {
    // Find the first step that has not been completed, mark it as rejected
    const firstIncompleteIndex = steps.findIndex((s) => !s.completed_at);
    return steps.map((s, i) => {
      if (s.completed_at) {
        return { ...s, status: "completed" };
      }
      if (i === firstIncompleteIndex || firstIncompleteIndex === -1) {
        return { ...s, status: "rejected" };
      }
      return { ...s, status: "pending" };
    });
  }
  return steps;
});

const currentStep = computed(() => {
  return displayProgress.value.find((step) => step.status === "current");
});

// Show action buttons for current step user OR admin bypass
const shouldShowActionButtons = computed(() => {
  // Admin can see action buttons if they have any permissions (but only appropriate ones)
  if (props.userRole === "Admin") {
    return props.canVerify || props.canValidate || props.canApprove || props.canReject;
  }

  // Regular users only see buttons for their current step
  return (
    currentStep.value?.status === "current" && currentStep.value?.role === props.userRole
  );
});

const getStepIconClass = (status: string) => {
  switch (status) {
    case "completed":
      return "bg-green-100 text-green-600";
    case "current":
      return "bg-blue-100 text-blue-600";
    case "rejected":
      return "bg-red-100 text-red-600";
    default:
      return "bg-gray-100 text-gray-400";
  }
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case "completed":
      return "bg-green-100 text-green-800";
    case "current":
      return "bg-blue-100 text-blue-800";
    case "rejected":
      return "bg-red-100 text-red-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
};

const getStatusLabel = (status: string) => {
  switch (status) {
    case "completed":
      return "Selesai";
    case "current":
      return "Sedang Berlangsung";
    case "rejected":
      return "Ditolak";
    default:
      return props.purchaseOrder?.status === "Rejected" ? "Tidak Diproses" : "Menunggu";
  }
};

const getStepLabel = (step: string) => {
  switch (step) {
    case "verified":
      return "Verifikasi";
    case "validated":
      return "Validasi";
    case "approved":
      return "Persetujuan";
    default:
      return step;
  }
};

const getCompletionText = (step: string) => {
  switch (step) {
    case "verified":
      return "Diverifikasi";
    case "validated":
      return "Divalidasi";
    case "approved":
      return "Disetujui";
    default:
      return "Diselesaikan";
  }
};

const getCurrentActionDescription = () => {
  // Admin bypass description - admin follows workflow steps
  if (props.userRole === "Admin") {
    const actions = [];
    if (props.canVerify) actions.push("Verifikasi");
    if (props.canValidate) actions.push("Validasi");
    if (props.canApprove) actions.push("Setujui");
    if (props.canReject) actions.push("Tolak");

    if (actions.length > 0) {
      return `Admin dapat melakukan tahapan: ${actions.join(", ")}`;
    }
    return "Tidak ada tindakan yang dapat dilakukan";
  }

  // Regular user descriptions
  if (props.canVerify) return "Dokumen siap untuk diverifikasi atau ditolak";
  if (props.canValidate) return "Dokumen siap untuk divalidasi atau ditolak";
  if (props.canApprove) return "Dokumen siap untuk disetujui atau ditolak";
  if (props.canReject && currentStep.value?.status === "current")
    return "Dokumen dapat ditolak";
  return "Tidak ada tindakan yang dapat dilakukan";
};

const formatDateTime = (dateTime: string) => {
  return new Date(dateTime).toLocaleString("id-ID", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

// When rejected, show dash for pending steps instead of index number
const getPendingIconText = (index: number) => {
  return props.purchaseOrder?.status === "Rejected" ? "–" : index + 1;
};
</script>
