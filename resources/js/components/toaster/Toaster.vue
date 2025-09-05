<template>
  <div class="toaster-container fixed top-4 right-4 z-50 space-y-2">
    <TransitionGroup
      name="toast"
      tag="div"
      class="space-y-2"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'toast-item flex items-center p-4 rounded-lg shadow-lg max-w-sm w-full',
          'transform transition-all duration-300 ease-in-out',
          getToastClasses(toast.type)
        ]"
        @mouseenter="pauseTimer(toast.id)"
        @mouseleave="resumeTimer(toast.id)"
      >
        <!-- Icon -->
        <div class="flex-shrink-0 mr-3">
          <component :is="getToastIcon(toast.type)" class="w-5 h-5" />
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium" v-if="toast.title">{{ toast.title }}</p>
          <p class="text-sm" v-if="toast.message">{{ toast.message }}</p>
        </div>
        
        <!-- Close button -->
        <button
          @click="removeToast(toast.id)"
          class="flex-shrink-0 ml-3 opacity-70 hover:opacity-100 transition-opacity"
          :class="getCloseButtonClasses(toast.type)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        
        <!-- Progress bar -->
        <div
          v-if="toast.duration !== Infinity"
          class="absolute bottom-0 left-0 h-1 bg-current opacity-20 rounded-b-lg transition-all duration-100"
          :style="{ width: `${toast.progress}%` }"
        ></div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { 
  CheckCircle, 
  XCircle, 
  AlertTriangle, 
  Info
} from 'lucide-vue-next';
import { useToaster } from '@/composables/useToaster';

export interface Toast {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title?: string;
  message: string;
  duration: number;
  progress: number;
  timer?: number;
}

const toasts = ref<Toast[]>([]);
let nextId = 1;

// Toast type configurations
const toastConfigs = {
  success: {
    classes: 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
    icon: CheckCircle,
    closeButton: 'text-green-600 dark:text-green-400'
  },
  error: {
    classes: 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
    icon: XCircle,
    closeButton: 'text-red-600 dark:text-red-400'
  },
  warning: {
    classes: 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
    icon: AlertTriangle,
    closeButton: 'text-yellow-600 dark:text-yellow-400'
  },
  info: {
    classes: 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
    icon: Info,
    closeButton: 'text-blue-600 dark:text-blue-400'
  }
};

// Get toast classes based on type
const getToastClasses = (type: Toast['type']) => {
  return toastConfigs[type].classes;
};

// Get toast icon based on type
const getToastIcon = (type: Toast['type']) => {
  return toastConfigs[type].icon;
};

// Get close button classes based on type
const getCloseButtonClasses = (type: Toast['type']) => {
  return toastConfigs[type].closeButton;
};

// Add a new toast
const addToast = (toast: Omit<Toast, 'id' | 'progress'>) => {
  const newToast: Toast = {
    ...toast,
    id: `toast-${nextId++}`,
    progress: 100
  };
  
  toasts.value.push(newToast);
  
  // Start progress timer if duration is not infinite
  if (newToast.duration !== Infinity) {
    startProgressTimer(newToast);
  }
  
  // Auto-remove after duration
  if (newToast.duration !== Infinity) {
    newToast.timer = setTimeout(() => {
      removeToast(newToast.id);
    }, newToast.duration);
  }
};

// Start progress timer for visual feedback
const startProgressTimer = (toast: Toast) => {
  const startTime = Date.now();
  const updateProgress = () => {
    if (toast.progress > 0) {
      const elapsed = Date.now() - startTime;
      const remaining = toast.duration - elapsed;
      toast.progress = Math.max(0, (remaining / toast.duration) * 100);
      
      if (toast.progress > 0) {
        requestAnimationFrame(updateProgress);
      }
    }
  };
  
  requestAnimationFrame(updateProgress);
};

// Pause timer when hovering
const pauseTimer = (id: string) => {
  const toast = toasts.value.find(t => t.id === id);
  if (toast && toast.timer) {
    clearTimeout(toast.timer);
    toast.timer = undefined;
  }
};

// Resume timer when leaving
const resumeTimer = (id: string) => {
  const toast = toasts.value.find(t => t.id === id);
  if (toast && toast.duration !== Infinity) {
    const remainingTime = (toast.progress / 100) * toast.duration;
    toast.timer = setTimeout(() => {
      removeToast(id);
    }, remainingTime);
  }
};

// Remove a toast
const removeToast = (id: string) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index > -1) {
    const toast = toasts.value[index];
    if (toast.timer) {
      clearTimeout(toast.timer);
    }
    toasts.value.splice(index, 1);
  }
};

// Clear all toasts
const clearAll = () => {
  toasts.value.forEach(toast => {
    if (toast.timer) {
      clearTimeout(toast.timer);
    }
  });
  toasts.value = [];
};

// Expose methods for external use
defineExpose({
  addToast,
  removeToast,
  clearAll
});

// Register with composable
onMounted(() => {
  const { setToasterInstance } = useToaster();
  setToasterInstance({
    addToast,
    removeToast,
    clearAll
  });
});

// Cleanup on unmount
onUnmounted(() => {
  clearAll();
});
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
