<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes_archived', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_nis')->index();
            $table->string('user_name')->nullable();
            $table->unsignedInteger('candidate_id');
            $table->string('candidate_name')->nullable();
            $table->timestamp('voted_at');
            $table->string('tahun');
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes_archived');
    }
};
