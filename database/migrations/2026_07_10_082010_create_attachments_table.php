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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // type: 'image' | 'link'
            $table->string('type', 20);
            // For images: storage path; for links: the URL
            $table->string('path');
            // Human-readable filename / link title
            $table->string('name')->nullable();
            // MIME type for images
            $table->string('mime_type', 100)->nullable();
            // File size in bytes for images
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['card_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
