<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the old belum_voting status to aktif.
     */
    public function up(): void
    {
        foreach (['users', 'users_archived'] as $tableName) {
            DB::table($tableName)
                ->where('status', 'belum_voting')
                ->update(['status' => 'aktif']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['users', 'users_archived'] as $tableName) {
            DB::table($tableName)
                ->where('status', 'aktif')
                ->update(['status' => 'belum_voting']);
        }
    }
};
