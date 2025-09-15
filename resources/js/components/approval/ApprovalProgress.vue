<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Progress Approval</h3>

    <div class="space-y-3">
      <div
        v-for="(step, index) in progress"
        :key="step.step"
        class="flex items-center space-x-3"
      >
        <!-- Step Icon -->
        <div
          class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          :class="getStepIconClass(step.status)"
        >
          <CheckIcon v-if="step.status === 'completed'" class="w-4 h-4" />
          <ClockIcon v-else-if="step.status === 'current'" class="w-4 h-4" />
          <span v-else>{{ index + 1 }}</span>
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
              class="px-2 py-1 text-xs font-medium rounded-full"
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
        <div
          v-if="index < progress.length - 1"
          class="absolute left-4 top-8 w-0.5 h-6 bg-gray-200"
        ></div>
      </div>
    </div>

    <!-- Current Action Buttons -->
    <div
      v-if="currentStep?.status === 'current' && currentStep?.role === props.userRole"
      class="mt-6 pt-4 border-t border-gray-200"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-900">Tindakan yang dapat dilakukan:</p>
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
            v-if="
              canReject &&
              currentStep?.status === 'current' &&
              currentStep?.role === props.userRole
            "
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
  status: "pending" | "current" | "completed";
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

const currentStep = computed(() => {
  return props.progress.find((step) => step.status === "current");
});

// const isCurrentStepCompleted = computed(() => {
//   if (!currentStep.value) return false;
//   return currentStep.value.status === "completed";
// });

// const hasApprovedOrVerified = computed(() => {
//   return props.progress.some(
//     (step) =>
//       (step.step === "verified" ||
//         step.step === "validated" ||
//         step.step === "approved") &&
//       step.status === "completed"
//   );
// });

const getStepIconClass = (status: string) => {
  switch (status) {
    case "completed":
      return "bg-green-100 text-green-600";
    case "current":
      return "bg-blue-100 text-blue-600";
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
    default:
      return "Menunggu";
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
</script>
