import {
  Landmark,
  UsersRound,
  Handshake,
  ReceiptText,
  Wallet,
  SquareUserRound,
  CreditCard,
  WalletCards,
  TicketPercent,
  FileText,
  Wallet2,
  Grid2x2Check,
  SquareCheck,
  Clipboard,
  FolderSync,
  NotebookPen,
  UserSearch,
  UserPen,
  NotepadTextDashed,
  BookOpenCheck
} from 'lucide-vue-next';

export const iconMapping = {
  // Master
  'banks': Landmark,
  'bank-accounts': Landmark,
  'suppliers': UsersRound,
  'bisnis-partners': Handshake,
  'pphs': ReceiptText,
  'pengeluarans': Wallet,
  'ar-partners': SquareUserRound,

  // Daily Use
  'purchase-orders': CreditCard,
  'memo-pembayaran': WalletCards,
  'payment-voucher': TicketPercent,
  'bpb': FileText,
  'po-anggaran': Wallet2,
  'anggaran': Wallet2,
  'realisasi': Grid2x2Check,
  'approval': SquareCheck,
  'daftar-list-bayar': Clipboard,

  // Bank
  'bank-matching': FolderSync,
  'bank-masuk': Landmark,
  'bank-keluar': Landmark,

  // Report
  'po-outstanding': NotebookPen,

  // Setting
  'roles': UserSearch,
  'departments': UsersRound,
  'termins': NotepadTextDashed,
  'perihals': NotepadTextDashed,
  'users': UserPen,
};

export const getIconForRoute = (route: string): any => {
  // Extract the main route segment
  const routeSegment = route.split('/')[1];
  return iconMapping[routeSegment as keyof typeof iconMapping] || Landmark;
};

export const getIconForPage = (pageName: string): any => {
  // Map page names to icons - ensuring they match the navigation items exactly
  const pageIconMap: Record<string, any> = {
    'Bank': Landmark,
    'Bank Account': Landmark,
    'Supplier': UsersRound,
    'Bisnis Partner': Handshake,
    'PPh': ReceiptText,
    'Pengeluaran': Wallet,
    'Customer': SquareUserRound,
    'Purchase Order': CreditCard,
    'Memo Pembayaran': WalletCards,
    'Payment Voucher': TicketPercent,
    'BPB': FileText,
    'PO Anggaran': Wallet2,
    'Anggaran': Wallet2,
    'Realisasi': Grid2x2Check,
    'Approval': SquareCheck,
    'Daftar List Bayar': Clipboard,
    'Bank Matching': FolderSync,
    'Bank Masuk': Landmark,
    'Bank Keluar': Landmark,
    'PO Outstanding': NotebookPen,
    'Role': UserSearch,
    'Department': UsersRound,
    'Termin': BookOpenCheck,
    'Perihal': NotepadTextDashed,
    'User': UserPen,
    'Users': UserPen,
  };

  return pageIconMap[pageName] || Landmark;
};
