import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
  const page = usePage()

  const user = computed(() => page.props.auth?.user)
  const permissions = computed(() => user.value?.role?.permissions || [])
  const roleName = computed(() => user.value?.role?.name)

  const hasPermission = (permission: string): boolean => {
    if (!permissions.value) return false

    // Admin has all permissions
    if (permissions.value.includes('*')) return true

    return permissions.value.includes(permission)
  }

  const hasAnyPermission = (permissionList: string[]): boolean => {
    if (!permissions.value) return false

    // Admin has all permissions
    if (permissions.value.includes('*')) return true

    return permissionList.some(permission => permissions.value.includes(permission))
  }

  const hasAllPermissions = (permissionList: string[]): boolean => {
    if (!permissions.value) return false

    // Admin has all permissions
    if (permissions.value.includes('*')) return true

    return permissionList.every(permission => permissions.value.includes(permission))
  }

  const hasRole = (roleName: string): boolean => {
    return user.value?.role?.name === roleName
  }

  const hasAnyRole = (roleNames: string[]): boolean => {
    return roleNames.includes(user.value?.role?.name || '')
  }

  const canAccessMenu = (menuPermission: string): boolean => {
    return hasPermission(menuPermission)
  }

  // Menu access helpers based on role definitions
  const canAccessPurchaseOrder = computed(() => hasPermission('purchase_order'))
  const canAccessBank = computed(() => hasPermission('bank'))
  const canAccessSupplier = computed(() => hasPermission('supplier'))
  const canAccessBisnisPartner = computed(() => hasPermission('bisnis_partner'))
  const canAccessBankMasuk = computed(() => hasPermission('bank_masuk'))
  const canAccessBankMatching = computed(() => hasPermission('bank_masuk'))
  const canAccessPaymentVoucher = computed(() => hasPermission('payment_voucher'))
  const canAccessApproval = computed(() => hasPermission('approval'))
  const canAccessMasterData = computed(() => hasPermission('*'))

  return {
    user,
    permissions,
    roleName,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    canAccessMenu,
    canAccessPurchaseOrder,
    canAccessBank,
    canAccessSupplier,
    canAccessBisnisPartner,
    canAccessBankMasuk,
    canAccessBankMatching,
    canAccessPaymentVoucher,
    canAccessApproval,
    canAccessMasterData,
  }
}
