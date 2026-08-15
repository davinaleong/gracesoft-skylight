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
        if (Schema::hasColumn('boards', 'workspace_id')) {
            Schema::table('boards', function (Blueprint $table) {
                $table->dropForeign('boards_workspace_id_foreign');
            });

            Schema::table('boards', function (Blueprint $table) {
                $table->dropColumn('workspace_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('boards', 'workspace_id')) {
            Schema::table('boards', function (Blueprint $table) {
                $table->unsignedBigInteger('workspace_id')->nullable();
            });
        }
    }
};
