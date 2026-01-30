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
    children?: NavItem[];
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
    unique_id: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}
export interface ModelData {
  name: string
  content: string
  class_meta: Array<{
    name: string
    value: string
  }>
  class_methods: Array<{
    name: string
    params: string[]
    content: string
  }>
  inheritance: string
}

export interface RouteData {
  description: string
  path: string
  verb: string
  view_name: string
  required?: Array<{
    name: string
    example?: string
    description?: string
  }>
  optional?: Array<{
    name: string
    example?: string
    description?: string
  }>
}

export interface ResourceData{
  name: string
  type: string
  model_name?: string | null
}


export interface ApiData { // TO:DO -- API Version Data
  id: number;
  api: number; 
  summary: string | null;
  description: string | null;
  stable: boolean;
  version_models: ModelData[];
  version_views: ApiVersionFunction[]; 
  version_urls: RouteData[]; 
  options: any; 
  version_files: any[]; 
  resources: ResourceData[]; 
  created_at: string; 
  updated_at: string; 
  created_by: number; 
  updated_by: number; 
}

export interface ApiVersionFunction {
    name: string;
    content: string;
}

export interface Api {
  id: number
  name: string
  description: string
  tags: string
  api_type: string
  user_id: number
  created_at: string
  updated_at: string
  created_by_id: number
  updated_by_id: number
  deleted_at: string | null
}

interface ApiInstanceRouteUserMap {
  verb: string,
  route: string,
  api_user: string
}

interface ApiInstanceResource {
  name: string,
  resource: string
}

export interface ApiInstanceOptions {
  [key: string]: string | number | boolean | null | undefined | ApiInstanceOptions | Array<string | number | boolean | ApiInstanceOptions>
}

export interface ApiInstance {
  id: number
  api_id: number
  api_version_id: number | null
  environment_id: number
  name: string
  route: string
  slug?: string
  route_user_map: ApiInstanceRouteUserMap[],
  resources: ApiInstanceResource[],
  options: ApiInstanceOptions,
  public: number
  created_at: string
  updated_at: string
  api: Api
  api_version: ApiData
  environment: Environment
  api_type?: string
}

export interface Environment {
  id: number,
  domain: string,
  name: string,
  type: string,
  created_at: string,
  updated_at: string,
  deleted_at: string | null
}

export interface ApiUser {
  id: number,
  is_active: number,
  created_at: string,
  environment_id: number,
  app_name: string,
  app_secret: string,
  api_key: string,
  api_type?: string,
}

interface ResourceConfig {
  tns: string,
  pass: string,
  user: string,
  name?: string,
  server?: string,
  value?: string
}

export interface Resource {
  id: number,
  name: string,
  config: ResourceConfig | null,
  type: string,
  created_at: string,
  updated_at: string,
  resource_type: string,
}

export interface ProxyServer {
  id: number,
  name: string,
  slug: string,
  server: string,
  type: string,
  username: string,
  password: string,
  is_active: boolean,
}
export type BreadcrumbItemType = BreadcrumbItem;
