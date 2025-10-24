import { ref } from 'vue';

interface AlertOptions {
  type?: 'info' | 'warning' | 'error' | 'success';
  title?: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  showCancel?: boolean;
  // Optional auto close in milliseconds (ignored when showCancel=true)
  autoCloseMs?: number;
}

interface AlertState {
  isOpen: boolean;
  type: 'info' | 'warning' | 'error' | 'success';
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  showCancel: boolean;
  resolve?: (value: boolean) => void;
}

const alertState = ref<AlertState>({
  isOpen: false,
  type: 'info',
  title: 'Informasi',
  message: '',
  confirmText: 'OK',
  cancelText: 'Batal',
  showCancel: false,
});

export function useAlertDialog() {
  // Keep reference to the current auto close timer (if any)
  let autoCloseTimer: ReturnType<typeof setTimeout> | null = null;

  const showAlert = (options: AlertOptions): Promise<boolean> => {
    return new Promise((resolve) => {
      // If an alert is already open, close it before showing a new one
      if (alertState.value.isOpen) {
        closeAlert(false);
      }
      alertState.value = {
        isOpen: true,
        type: options.type || 'info',
        title: options.title || getDefaultTitle(options.type || 'info'),
        message: options.message,
        confirmText: options.confirmText || 'OK',
        cancelText: options.cancelText || 'Batal',
        showCancel: options.showCancel || false,
        resolve,
      };

      // Set up auto close for non-confirm alerts
      if (!alertState.value.showCancel) {
        if (autoCloseTimer) clearTimeout(autoCloseTimer);
        const timeout = typeof options.autoCloseMs === 'number' ? options.autoCloseMs : 3500;
        autoCloseTimer = setTimeout(() => {
          closeAlert(false);
        }, Math.max(0, timeout));
      }
    });
  };

  const showInfo = (message: string, title?: string): Promise<boolean> => {
    return showAlert({ type: 'info', message, title });
  };

  const showWarning = (message: string, title?: string): Promise<boolean> => {
    return showAlert({ type: 'warning', message, title });
  };

  const showError = (message: string, title?: string): Promise<boolean> => {
    return showAlert({ type: 'error', message, title });
  };

  const showSuccess = (message: string, title?: string): Promise<boolean> => {
    return showAlert({ type: 'success', message, title });
  };

  const showConfirm = (message: string, title?: string): Promise<boolean> => {
    return showAlert({
      type: 'warning',
      message,
      title: title || 'Konfirmasi',
      showCancel: true,
      confirmText: 'Ya',
      cancelText: 'Tidak'
    });
  };

  const closeAlert = (confirmed: boolean = false) => {
    // Clear any pending auto close
    if (autoCloseTimer) {
      clearTimeout(autoCloseTimer);
      autoCloseTimer = null;
    }
    if (alertState.value.resolve) {
      alertState.value.resolve(confirmed);
    }
    alertState.value.isOpen = false;
  };

  const handleConfirm = () => {
    closeAlert(true);
  };

  const handleCancel = () => {
    closeAlert(false);
  };

  function getDefaultTitle(type: string): string {
    switch (type) {
      case 'success':
        return 'Berhasil';
      case 'error':
        return 'Error';
      case 'warning':
        return 'Peringatan';
      case 'info':
      default:
        return 'Informasi';
    }
  }

  return {
    alertState,
    showAlert,
    showInfo,
    showWarning,
    showError,
    showSuccess,
    showConfirm,
    closeAlert,
    handleConfirm,
    handleCancel,
  };
}
