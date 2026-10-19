export interface DashboardStats {
    total_pemilih: number;
    sudah_voting: number;
    belum_voting: number;
    partisipasi: number;
}

export interface PerolehanItem {
    nomor_urut: number;
    nama_lengkap: string;
    votes: number;
    percentage: number;
}

export interface KelasDistribusi {
    kelas: string;
    sudah: number;
    belum: number;
    total: number;
    persentase: number;
}

export interface AktivitasTerbaru {
    id: number;
    aksi: string;
    detail: string | null;
    ip_address: string | null;
    waktu: string;
    nama_pemilih: string;
    kelas: string | null;
    nomor_urut: number | null;
    nama_kandidat: string | null;
}

export interface DashboardProps {
    stats: DashboardStats;
    perolehan: PerolehanItem[];
    kelas: KelasDistribusi[];
    aktivitas: AktivitasTerbaru[];
}
