import { ref } from 'vue';
import type { Toast } from '@/components/toaster/Toaster.vue';

// Global toaster instance
const toasterInstance = ref<any>(null);

// Toast methods
const addToast = (toast: Omit<Toast, 'id' | 'progress'>) => {
  if (toasterInstance.value) {
    toasterInstance.value.addToast(toast);
  } else {
    console.warn('Toaster not initialized. Make sure Toaster component is mounted.');
  }
};

const removeToast = (id: string) => {
  if (toasterInstance.value) {
    toasterInstance.value.removeToast(id);
  }
};

const clearAll = () => {
  if (toasterInstance.value) {
    toasterInstance.value.clearAll();
  }
};

// Convenience methods for different toast types
const success = (message: string, title?: string, duration: number = 5000) => {
  addToast({ type: 'success', message, title, duration });
};

const error = (message: string, title?: string, duration: number = 7000) => {
  addToast({ type: 'error', message, title, duration });
};

const warning = (message: string, title?: string, duration: number = 5000) => {
  addToast({ type: 'warning', message, title, duration });
};

const info = (message: string, title?: string, duration: number = 4000) => {
  addToast({ type: 'info', message, title, duration });
};

// Set the toaster instance (called by Toaster component)
const setToasterInstance = (instance: any) => {
  toasterInstance.value = instance;
};

export function useToaster() {
  return {
    addToast,
    removeToast,
    clearAll,
    success,
    error,
    warning,
    info,
    setToasterInstance
  };
}
