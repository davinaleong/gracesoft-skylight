<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('share_link_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_share_link_id')->constrained()->cascadeOnDelete();
            // Hashed IP — privacy-preserving
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('accessed_at');

            $table->index(['board_share_link_id', 'accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_link_accesses');
    }
};
