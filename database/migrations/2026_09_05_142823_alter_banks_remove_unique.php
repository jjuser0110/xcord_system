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
        Schema::table('banks', function (Blueprint $table) {
            // 1. Drop the old strict unique indexes
            $table->dropUnique(['bank_name']);
            $table->dropUnique(['short_name']);

            // 2. Add new composite unique indexes including deleted_at
            $table->unique(['bank_name', 'deleted_at']);
            $table->unique(['short_name', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique(['bank_name', 'deleted_at']);
            $table->dropUnique(['short_name', 'deleted_at']);

            $table->unique('bank_name');
            $table->unique('short_name');
        });
    }
};
