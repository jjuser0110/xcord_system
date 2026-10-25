<?php

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
        Schema::table('bank_settings', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active')->index();
        });

        // Backfill so the list keeps its current order (newest on top, highest sort_order first)
        $ids = DB::table('bank_settings')->orderBy('created_at')->orderBy('id')->pluck('id');
        foreach ($ids as $index => $id) {
            DB::table('bank_settings')->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_settings', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
