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
        Schema::table('purposes', function (Blueprint $table) {
            $table->enum('money_flow_type', ['bank_in', 'bank_out', 'both'])->default('both')->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purposes', function (Blueprint $table) {
            $table->dropColumn('money_flow_type');
        });
    }
};
