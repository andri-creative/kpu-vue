import type { PaginationLink } from './pagination';

export interface RoleEnum {
    value: string;
    label?: string;
}

export interface UserRow {
    id: string;
    nis: string;
    role: string;
    status: string;
    nama_lengkap: string | null;
    kelas: string | null;
    kode: string | null;
    created_at: string;
}

export interface PaginatedUsers {
    data: UserRow[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface UserData {
    id: string;
    nis: string;
    role: string;
}

export interface AdminRow {
    id: string;
    nis: string;
    status: string;
    profile: { nama_lengkap: string } | null;
    created_at: string;
}

export interface AdminsPagination {
    data: AdminRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}
