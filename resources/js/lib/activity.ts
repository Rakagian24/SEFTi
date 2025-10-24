import {
  Activity,
  Plus,
  Edit,
  Trash2,
  Send,
  Check,
  X,
  Eye,
  ArrowRight,
} from "lucide-vue-next";

function norm(action: string) {
  return (action || "").toString().trim().toLowerCase();
}

export function getActionDescription(action: string, entityLabel: string) {
  switch (norm(action)) {
    // CRUD
    case "created":
    case "create":
      return `Mengirimkan data ${entityLabel}`;
    case "updated":
    case "update":
      return `Mengubah data ${entityLabel}`;
    case "deleted":
    case "delete":
      return `Menghapus data ${entityLabel}`;

    // Submissions / communications
    case "sent":
    case "submit":
    case "submitted":
      return `Mengirimkan data ${entityLabel}`;

    // Workflow status
    case "draft":
    case "saved_draft":
      return `Menyimpan ${entityLabel} sebagai Draft`;
    case "in progress":
      return `Memproses ${entityLabel}`;
    case "verified":
    case "verify":
      return `Memverifikasi ${entityLabel}`;
    case "validated":
    case "validate":
      return `Memvalidasi ${entityLabel}`;
    case "approved":
    case "approve":
      return `Menyetujui ${entityLabel}`;
    case "rejected":
    case "reject":
      return `Menolak ${entityLabel}`;
    case "canceled":
    case "cancel":
    case "cancelled":
      return `Membatalkan ${entityLabel}`;

    // Other common actions
    case "processed":
    case "process":
      return `Memproses ${entityLabel}`;
    case "paid":
    case "pay":
      return `Membayar ${entityLabel}`;
    case "reviewed":
    case "review":
      return `Meninjau ${entityLabel}`;
    case "viewed":
    case "view":
      return `Melihat ${entityLabel}`;
    case "received":
      return `Menerima ${entityLabel}`;
    case "returned":
      return `Mengembalikan ${entityLabel}`;
    case "out":
      return `Mengeluarkan ${entityLabel}`;

    default:
      return action;
  }
}

export function getActivityIcon(action: string) {
  switch (norm(action)) {
    // CRUD
    case "created":
    case "create":
      return Plus;
    case "updated":
    case "update":
      return Edit;
    case "deleted":
    case "delete":
      return Trash2;

    // Submissions
    case "sent":
    case "submit":
    case "submitted":
      return Send;

    // Workflow status
    case "verified":
    case "verify":
    case "validated":
    case "validate":
    case "approved":
    case "approve":
    case "paid":
    case "pay":
    case "received":
      return Check;
    case "rejected":
    case "reject":
    case "canceled":
    case "cancel":
    case "cancelled":
    case "returned":
    case "out":
      return X;

    // Others
    case "processed":
    case "process":
      return ArrowRight;
    case "reviewed":
    case "review":
    case "viewed":
    case "view":
      return Eye;

    default:
      return Activity;
  }
}

export function getActivityColor(action: string) {
  const a = norm(action);
  // Latest item accent stays via dot-glow in templates; here we provide color scale only
  if (
    a === "approved" ||
    a === "approve" ||
    a === "verified" ||
    a === "verify" ||
    a === "validated" ||
    a === "validate" ||
    a === "paid" ||
    a === "pay" ||
    a === "received"
  ) {
    return "bg-blue-600";
  }
  if (
    a === "rejected" ||
    a === "reject" ||
    a === "canceled" ||
    a === "cancel" ||
    a === "cancelled" ||
    a === "deleted" ||
    a === "delete" ||
    a === "returned" ||
    a === "out"
  ) {
    return "bg-blue-600";
  }
  if (a === "reviewed" || a === "review" || a === "viewed" || a === "view") {
    return "bg-blue-600";
  }
  // created/updated/sent/processed/draft/in progress => blue
  return "bg-blue-600";
}
