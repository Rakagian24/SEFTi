<script setup lang="ts">
// import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
// import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
// import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Clipboard, CreditCard, FileText, Grid2x2Check, Handshake, Landmark, NotepadText, ReceiptText, SquareCheck, TicketPercent, UserPen, UserSearch, UsersRound, Wallet, Wallet2, WalletCards } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';


const mainNavGroups = [
  {
    label: 'Master',
    items: [
      { title: 'Bank', href: '/banks', icon: Landmark },
      { title: 'Supplier', href: '/supplier', icon: UsersRound },
      { title: 'Bisnis Partner', href: '/bisnis-partners', icon: Handshake },
      { title: 'PPh', href: '/pph', icon: ReceiptText },
      { title: 'Pengeluaran', href: '/pengeluaran', icon: Wallet },
      { title: 'AR Partner', href: '/ar-partners', icon: NotepadText },
    ]
  },
  {
    label: 'Daily Use',
    items: [
      { title: 'Purchase Order', href: '/purchase-order', icon: CreditCard },
      { title: 'Memo Pembayaran', href: '/memo-pembayaran', icon: WalletCards },
      { title: 'BPB', href: '/bpb', icon: FileText },
      { title: 'Realisasi', href: '/realisasi', icon: Grid2x2Check },
      { title: 'Anggaran', href: '/anggaran', icon: Wallet2 },
      { title: 'Payment Voucher', href: '/payment-voucher', icon: TicketPercent },
      { title: 'Approval', href: '/approval', icon: SquareCheck },
      { title: 'Daftar List Bayar', href: '/daftar-list-bayar', icon: Clipboard },
    ]
  },
  {
    label: 'Bank',
    items: [
      { title: 'Bank Masuk', href: '/bank-masuk', icon: Landmark },
      { title: 'Bank Keluar', href: '/bank-keluar', icon: Landmark },
    ]
  },
  {
    label: 'Report',
    items: [
      { title: 'PO Outstanding', href: '/po-outstanding', icon: NotepadText },
    ]
  },
  {
    label: 'Setting',
    items: [
      { title: 'Role', href: '/role', icon: UserSearch },
      { title: 'Users', href: '/users', icon: UserPen },
    ]
  },
];

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="mainNavGroups" />
        </SidebarContent>

        <!-- <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter> -->
    </Sidebar>
    <slot />
</template>
