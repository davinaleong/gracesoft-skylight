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
        Schema::create('board_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            // 32-byte random token, base62-encoded, stored as SHA-256 hash
            $table->string('token_hash', 64)->unique();
            $table->boolean('can_see_comments')->default(false);
            $table->boolean('can_see_attachments')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('board_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_share_links');
    }
};
