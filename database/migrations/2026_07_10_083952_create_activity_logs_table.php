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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // e.g. 'board.created', 'card.moved', 'login.success', '2fa.verified'
            $table->string('event', 100);
            // Polymorphic subject: what was acted on
            $table->nullableMorphs('subject');
            // Field diffs for *.updated events: ['field' => ['old' => x, 'new' => y]]
            $table->json('properties')->nullable();
            // Hashed IP — never raw
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
