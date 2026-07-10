<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add nullable first so existing rows don't violate the NOT NULL constraint
        Schema::table('boards', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Backfill UUIDs for any rows that already exist
        DB::table('boards')->whereNull('uuid')->lazyById()->each(function (object $board) {
            DB::table('boards')
                ->where('id', $board->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        // Now make the column non-nullable
        Schema::table('boards', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
