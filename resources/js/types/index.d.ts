import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}
interface ModelData {
  name: string
  content: string
  class_meta: Array<{
    name: string
    value: string
  }>
  inheritance: string
}

export interface ApiData {
  id: number;
  api: number; 
  summary: string | null;
  description: string | null;
  stable: boolean;
  version_models: ModelData[];
  version_views: ApiVersionFunction[]; 
  version_urls: ApiVersionUrl[]; 
  options: any[]; 
  version_files: any[]; 
  resources: Record<string, any>; 
  created_at: string; 
  updated_at: string; 
  created_by: number; 
  updated_by: number; 
}

export interface ApiVersionUrl {
  id: number
  view_name: string
  path: string
  verb: string
  required: RouteParams[]
  optional: RouteParams[]
}

export interface ApiVersionFunction {
    name: string;
    content: string;
}


export type BreadcrumbItemType = BreadcrumbItem;
