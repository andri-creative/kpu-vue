<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_archived', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nis')->index();
            $table->string('role');
            $table->string('status');
            $table->string('tahun');
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_archived');
    }
};
