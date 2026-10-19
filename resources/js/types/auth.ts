export type Profile = {
    id: number;
    user_id: string;
    nama_lengkap: string;
    kelas: string;
};

export type User = {
    id: string;
    nis: string;
    role: string;
    status: string;
    profile?: Profile | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
