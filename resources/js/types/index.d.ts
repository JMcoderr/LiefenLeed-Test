import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface User {
    email: string;
    admin: AdminAuth;
    expires_at: Date
}

export interface AdminAuth {
    isAdmin: boolean;
    isSuper: boolean;
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

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

// export interface User {
//     id: number;
//     name: string;
//     email: string;
//     avatar?: string;
//     email_verified_at: string | null;
//     created_at: string;
//     updated_at: string;
// }
export interface Member {
    id: number;
    full_name: string;
    name: string;
    email: string;
    dob: string;
    years_of_service: string;
    created_at: Date;
    updated_at: Date;
    deleted_at: Date;
    requests?: Request[];
}

export interface Admin {
    id: number;
    employee: string;
    super: boolean;
    deleted_at: Date;
    created_at: Date;
    updated_at: Date;
}

export interface EventType {
    id: number;
    title: string;
    event_costs: EventCost[];
    current_cost: EventCost;
}

export interface EventCost {
    id: number;
    event_id: number;
    amount: number;
    start_date: Date;
    end_date: Date;
    status: string;
    event: EventType;
}

export interface Request {
    id: number;
    employee_requester: string;
    employee_recipient: string;
    event_cost_id: number;
    account_name: string;
    status: string;
    created_at: Date;
    updated_at: Date;
    paid_at: Date;
    event_cost: EventCost;
    event: EventType;
    member?: Member;
}

export interface PaginatedResponse<T = Request | Member | null> {
    current_page: number;
    data?: T[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export type BreadcrumbItemType = BreadcrumbItem;
