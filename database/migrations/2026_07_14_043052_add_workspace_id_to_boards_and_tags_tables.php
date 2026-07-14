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
        // Add nullable first so existing rows don't violate the NOT NULL constraint.
        Schema::table('boards', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        // Any user that predates the workspace model (added the previous migration) has none yet.
        // New users get one automatically via UserObserver — this is a one-time backfill.
        DB::table('users')->lazyById()->each(function (object $user) {
            $hasWorkspace = DB::table('workspace_user')->where('user_id', $user->id)->exists();

            if ($hasWorkspace) {
                return;
            }

            $workspaceId = DB::table('workspaces')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'owner_id' => $user->id,
                'name' => $user->name."'s Workspace",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('workspace_user')->insert([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Scope every existing board/tag to its creator's personal (owner-role) workspace.
        DB::table('boards')->whereNull('workspace_id')->lazyById()->each(function (object $board) {
            $workspaceId = DB::table('workspace_user')
                ->where('user_id', $board->user_id)
                ->where('role', 'owner')
                ->value('workspace_id');

            DB::table('boards')->where('id', $board->id)->update(['workspace_id' => $workspaceId]);
        });

        DB::table('tags')->whereNull('workspace_id')->lazyById()->each(function (object $tag) {
            $workspaceId = DB::table('workspace_user')
                ->where('user_id', $tag->user_id)
                ->where('role', 'owner')
                ->value('workspace_id');

            DB::table('tags')->where('id', $tag->id)->update(['workspace_id' => $workspaceId]);
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable(false)->change();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable(false)->change();
            // MySQL needs a supporting index for the user_id FK before the
            // composite unique (which currently doubles as that index) can drop.
            $table->index('user_id');
            $table->dropUnique(['user_id', 'name']);
            $table->unique(['workspace_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['tags_workspace_id_name_unique']);
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropIndex(['user_id']);
            $table->unique(['user_id', 'name']);
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
