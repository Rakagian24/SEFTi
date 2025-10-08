import { ref } from 'vue';

interface AlertOptions {
  type?: 'info' | 'warning' | 'error' | 'success';
  title?: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  showCancel?: boolean;
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
  const showAlert = (options: AlertOptions): Promise<boolean> => {
    return new Promise((resolve) => {
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
