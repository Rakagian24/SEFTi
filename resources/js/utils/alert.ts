// Utility functions for easy alert usage
import { useAlertDialog } from '@/composables/useAlertDialog';

// Create a global instance for easy access
const { showAlert, showInfo, showWarning, showError, showSuccess, showConfirm } = useAlertDialog();

// Export the functions for easy import
export const alert = {
  info: showInfo,
  warning: showWarning,
  error: showError,
  success: showSuccess,
  confirm: showConfirm,
  custom: showAlert,
};

// Convenience functions that match common alert patterns
export const showAlertInfo = showInfo;
export const showAlertWarning = showWarning;
export const showAlertError = showError;
export const showAlertSuccess = showSuccess;
export const showAlertConfirm = showConfirm;

// Example usage:
// import { alert } from '@/utils/alert';
//
// // Simple alerts
// alert.info('This is an info message');
// alert.warning('This is a warning message');
// alert.error('This is an error message');
// alert.success('This is a success message');
//
// // Confirmation dialog
// const confirmed = await alert.confirm('Are you sure you want to delete this item?');
// if (confirmed) {
//   // User clicked "Ya"
// } else {
//   // User clicked "Tidak"
// }
//
// // Custom alert with options
// alert.custom({
//   type: 'warning',
//   title: 'Custom Title',
//   message: 'Custom message',
//   confirmText: 'OK',
//   showCancel: false
// });
