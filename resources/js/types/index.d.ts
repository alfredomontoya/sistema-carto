import type { PageProps as InertiaPageProps } from '@inertiajs/core';

export interface AreaData {
    id: string;
    parent_id: string | null;
    numbering_area_id?: string | null;
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
    path?: string;
    children?: AreaNode[];
}

export interface PositionData {
    id: string;
    name: string;
    code: string | null;
    is_active: boolean;
    sort_order: number;
    user_count: number;
}

export interface AreaNode {
    id: string;
    parent_id?: string | null;
    numbering_area_id?: string | null;
    numbering_area_name?: string | null;
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
    reset_annually: boolean;
    depth: number;
    position_count: number;
    user_count: number;
    positions: PositionData[];
    children: AreaNode[];
}

export interface RoleData {
    id: number;
    name: string;
}

export interface UserData {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    avatar_kind: 'gallery' | 'upload';
    avatar_value: string | null;
    avatar_url: string | null;
    is_active: boolean;
    created_at: string | null;
    roles: string[];
    role_ids: number[];
    current_position: { id: string; name: string; code: string | null } | null;
    current_area: AreaData | null;
    can: {
        manage_users: boolean;
        manage_areas: boolean;
        manage_settings: boolean;
    };
}

export interface BrandData {
    app_name: string;
    primary_color: string;
    secondary_color: string;
    logo_file: string | null;
    favicon_file: string | null;
    logo_url: string | null;
    favicon_url: string | null;
}

export interface CommunicationData {
    id: string;
    type: 'ci' | 'of';
    number: string;
    year: number;
    sequence: number;
    reference: string;
    recipient_name: string;
    recipient_position: string | null;
    status: 'activo' | 'anulado';
    file_name: string | null;
    file_url: string | null;
    download_url: string | null;
    created_at: string | null;
    area: AreaData | null;
    position: { id: string; name: string } | null;
    user: UserData | null;
    recipient_user: UserData | null;
    can_edit: boolean;
    can_annul: boolean;
}

export interface PositionHistoryEntry {
    id: string;
    position_name: string;
    area_name: string;
    started_at: string | null;
    ended_at: string | null;
}

export interface PaginationData {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface CounterState {
    sequence: number;
    next_number: string | null;
}

export interface CountersData {
    ci: CounterState;
    of: CounterState;
    numbering_area: { code: string; name: string } | null;
}

export interface FlashData {
    success?: string | null;
    error?: string | null;
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps {
        auth: {
            user: UserData | null;
        };
        brand: BrandData;
        flash: FlashData;
        created?: CommunicationData | null;
    }
}

export {};
