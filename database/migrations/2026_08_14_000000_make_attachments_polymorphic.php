<?php

use App\Models\Card;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['card_id']);
            $table->unsignedBigInteger('card_id')->nullable()->change();
            $table->nullableMorphs('attachable');
        });

        DB::table('attachments')->update([
            'attachable_type' => Card::class,
            'attachable_id' => DB::raw('card_id'),
        ]);

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['card_id', 'type']);
            $table->dropColumn('card_id');
            $table->string('attachable_type')->nullable(false)->change();
            $table->unsignedBigInteger('attachable_id')->nullable(false)->change();
            $table->index(['attachable_type', 'attachable_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['attachable_type', 'attachable_id', 'type']);
            $table->foreignId('card_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('attachments')
            ->where('attachable_type', Card::class)
            ->update(['card_id' => DB::raw('attachable_id')]);

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropMorphs('attachable');
            $table->unsignedBigInteger('card_id')->nullable(false)->change();
            $table->index(['card_id', 'type']);
        });
    }
};
