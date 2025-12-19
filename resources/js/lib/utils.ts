import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Ref, WritableComputedRef } from 'vue'
import type { ApiData, ApiInstance } from '../types';


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
    version_urls: php.routes?.map((item: any) => ({
      path: item.path,
      verb: item.verb,
      optional: item.optional || [],
      required: item.required || [],
      view_name: item.function_name
    })) ?? [],     // alias
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
    functions: api.version_views.map((func) => ({
      ...func,
      content: func.content ?? '' // Ensure content is never null/undefined -> Hermes explode() issue
    })),
    routes: api.version_urls.map((item) => ({ 
      path: item.path, 
      verb: item.verb, 
      optional: item.optional, 
      required: item.required, 
      function_name: item.view_name 
    })),

    resources: api.resources,
    options: api.options,

    user_id: api.updated_by,
    created_at: api.created_at,
    updated_at: api.updated_at, //Update before sending to backend
  };
}

export function mapPhpToApiInstance(php: any): ApiInstance {
  return {
    id: php.id,
    api_id: php.api_id,
    api_version_id: php.api_version_id,
    environment_id: php.environment_id,
    name: php.name,
    route: php.slug,                    // alias: slug -> route
    route_user_map: php.route_user_map?.map((item: any) => ({
      route: item.route,
      verb: item.verb,
      api_user: item.api_user,
    })) ?? [],
    resources: php.resources ?? [],
    options: php.options ?? {},
    public: php.public ? 1 : 0,         // convert boolean to number
    created_at: php.created_at,
    updated_at: php.updated_at,
    
    api: {
      id: php.api.id,
      api_type: 'php',
      name: php.api.name,
      description: php.api.description,
      tags: php.api.tags,
      user_id: php.api.user_id,
      created_by_id: php.api.user_id,
      updated_by_id: php.api.user_id,
      created_at: php.api.created_at,
      updated_at: php.api.updated_at,
      deleted_at: php.api.deleted_at,
    },
    
    api_version: mapPhpToApiData(php.api_version),
    
    environment: {
      id: php.environment.id,
      domain: php.environment.domain,
      name: php.environment.name,
      type: php.environment.type,
      created_at: php.environment.created_at,
      updated_at: php.environment.updated_at,
      deleted_at: php.environment.deleted_at,
    },
    
    api_type: 'php',                    
  };
}