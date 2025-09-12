// Button color classes for approval actions
export const approvalButtonClasses: Record<string, string> = {
  verify: "bg-teal-500 hover:bg-teal-600 text-white",
  validate: "bg-violet-500 hover:bg-violet-600 text-white",
  approve: "bg-green-500 hover:bg-green-600 text-white",
};

export function getApprovalButtonClass(action: string): string {
  return approvalButtonClasses[action] || "bg-gray-300 text-gray-700";
}
export const statusBadgeClasses: Record<string, string> = {
  Draft: "bg-gray-100 text-gray-800",
  "In Progress": "bg-blue-100 text-blue-800",
  Verified: "bg-teal-100 text-teal-800",
  Validated: "bg-violet-100 text-violet-800",
  Approved: "bg-green-100 text-green-800",
  Rejected: "bg-red-100 text-red-800",
  Canceled: "bg-orange-100 text-orange-800",
};

export function getStatusBadgeClass(status: string): string {
  return statusBadgeClasses[status] || statusBadgeClasses["Draft"];
}

export const statusDotClasses: Record<string, string> = {
  Draft: "bg-gray-500",
  "In Progress": "bg-blue-500",
  Verified: "bg-teal-500",
  Validated: "bg-violet-500",
  Approved: "bg-green-500",
  Rejected: "bg-red-500",
  Canceled: "bg-orange-500",
};

export function getStatusDotClass(status: string): string {
  return statusDotClasses[status] || statusDotClasses["Draft"];
}

