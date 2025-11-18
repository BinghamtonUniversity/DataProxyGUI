import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Ref, WritableComputedRef } from 'vue'
import type { ApiData } from '../types';


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

export function mapPhpToApiData(php: any): ApiData {
  return {
    id: php.id,
    api: php.api_id,                    // alias
    summary: php.summary ?? null,
    description: php.description ?? null,
    stable: Boolean(php.stable),

    version_models: [],                 // PHP does NOT send this
    version_views: php.functions ?? [], // alias
    version_urls: php.routes ?? [],     // alias
    version_files: php.files ?? [],     // alias

    resources: php.resources ?? [],
    options: php.options ?? {},

    created_at: php.created_at,
    updated_at: php.updated_at,
    created_by: php.user_id ?? 0,       // if null, fallback?
    updated_by: php.user_id ?? 0,       // same here
  };
}

export function mapDjangoToApiData(django: any): ApiData {
  return {
    id: django.id,
    api: django.api,
    summary: django.summary ?? null,
    description: django.description ?? null,
    stable: django.stable ?? false,

    version_models: django.version_models ?? [],
    version_views: django.version_views ?? [],             
    version_urls: django.version_urls ?? [],           
    version_files: django.version_files ?? [],           

    options: django.options ?? {},
    resources: django.resources ?? [],

    created_at: django.created_at,
    updated_at: django.updated_at,
    created_by: django.created_by,
    updated_by: django.updated_by,
  };
}

export function denormalizeToPhp(api: ApiData): any {
  return {
    id: api.id,
    api_id: api.api,
    summary: api.summary,
    description: api.description,
    stable: api.stable ? 1 : 0,

    files: api.version_files,
    functions: api.version_views,
    routes: api.version_urls,

    resources: api.resources,
    options: api.options,

    user_id: api.updated_by,
    created_at: api.created_at,
    updated_at: api.updated_at, //Update before sending to backend
  };
}


