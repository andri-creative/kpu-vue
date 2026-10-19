export interface ArsipStats {
    total_pemilih: number;
    total_votes: number;
    total_vote_logs: number;
    total_kandidat: number;
}

export interface ArchiveItem {
    tahun: string;
    votes: number;
    users: number;
}
