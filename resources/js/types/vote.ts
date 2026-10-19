export interface HasilStats {
    total_pemilih: number;
    sudah_voting: number;
    partisipasi: number;
}

export interface ResultItem {
    id: number;
    nama_lengkap: string;
    nomor_urut: number;
    foto_url: string | null;
    votes_count: number;
    percentage: number;
}

export interface Voter {
    id: string;
    user_name: string;
    nis: string;
    kelas: string;
    candidate_name: string;
    candidate_nomor: number;
    candidate_foto_url: string | null;
    voted_at: string;
}

export interface VotersPagination {
    data: Voter[];
    next_page_url: string | null;
    prev_page_url: string | null;
    current_page: number;
    last_page: number;
}
