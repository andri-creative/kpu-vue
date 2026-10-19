<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_logs_archived', function (Blueprint $table) {
            $table->id();
            $table->string('user_nis')->index();
            $table->string('user_name')->nullable();
            $table->unsignedInteger('candidate_id');
            $table->string('action');
            $table->text('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('tahun');
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_logs_archived');
    }
};
