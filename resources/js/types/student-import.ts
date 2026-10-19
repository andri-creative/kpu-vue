export interface PreviewRow {
    row: number;
    nis: string;
    nama: string;
    kelas: string;
    status: 'valid' | 'error';
    errors: string[];
}

export interface PreviewResult {
    header_valid: boolean;
    message: string | null;
    rows: PreviewRow[];
    total: number;
    valid: number;
    invalid: number;
    ok: boolean;
}
