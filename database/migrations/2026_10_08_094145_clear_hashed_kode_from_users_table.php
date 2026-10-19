<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kode lama tersimpan sebagai hash SHA-256 dan tidak dapat dipulihkan
     * menjadi 6 angka. Kosongkan agar bisa dibuat ulang lewat tombol
     * "Buat Kode untuk yang belum ada" atau "Reset Kode".
     */
    public function up(): void
    {
        DB::table('users')->whereNotNull('kode')->update(['kode' => null]);
    }

    /**
     * Hash lama tidak dapat dipulihkan kembali.
     */
    public function down(): void
    {
        //
    }
};
