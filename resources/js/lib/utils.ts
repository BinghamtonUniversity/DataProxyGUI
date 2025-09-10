import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Ref, WritableComputedRef } from 'vue'


export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}


export function valueUpdater<T extends Record<string, any>>(
  updaterOrValue: T | ((prev: T) => T),
  ref: Ref<T> | WritableComputedRef<T>
): void {
  if (typeof updaterOrValue === 'function') {
    ref.value = updaterOrValue(ref.value)
  } else {
    ref.value = updaterOrValue
  }
}

export const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};