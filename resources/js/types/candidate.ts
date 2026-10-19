import type { PaginationLink } from './pagination';

export interface Candidate {
    id: number;
    user_id: string;
    foto: string | null;
    foto_url: string | null;
    nama_lengkap: string;
    nomor_urut: number;
    nis: string;
    kelas: string;
    visi: string;
    misi: string;
}

export type Kandidat = Candidate;

export interface CandidatesPaginator {
    data: Candidate[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number | null;
}
