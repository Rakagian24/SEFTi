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
    <div v-if="canPerformAction" class="mt-6 pt-4 border-t border-gray-200">
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
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <CheckIcon class="w-4 h-4 mr-1" />
            Verifikasi
          </button>

          <button
            v-if="canValidate"
            @click="$emit('validate', purchaseOrder)"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
          >
            <CheckIcon class="w-4 h-4 mr-1" />
            Validasi
          </button>

          <button
            v-if="canApprove"
            @click="$emit('approve', purchaseOrder)"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
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

const canPerformAction = computed(() => {
  return props.canVerify || props.canValidate || props.canApprove || props.canReject;
});

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
  if (props.canVerify) return "Dokumen siap untuk diverifikasi";
  if (props.canValidate) return "Dokumen siap untuk divalidasi";
  if (props.canApprove) return "Dokumen siap untuk disetujui";
  if (props.canReject) return "Dokumen dapat ditolak";
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
