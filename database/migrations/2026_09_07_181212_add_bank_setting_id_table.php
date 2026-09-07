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
        // Update provider_settlements table
        Schema::table('provider_settlements', function (Blueprint $table) {
            $table->enum('type', ['in', 'out'])->after('bank_name')->default('in');
            $table->foreignId('bank_setting_id')->nullable()->constrained('bank_settings')->cascadeOnDelete();
        });

        // Update merchant_settlements table
        Schema::table('merchant_settlements', function (Blueprint $table) {
            $table->foreignId('bank_setting_id')->nullable()->constrained('bank_settings')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_settlements', function (Blueprint $table) {
            $table->dropForeign(['bank_setting_id']);
            $table->dropColumn(['type', 'bank_setting_id']);
        });

        Schema::table('merchant_settlements', function (Blueprint $table) {
            $table->dropForeign(['bank_setting_id']);
            $table->dropColumn('bank_setting_id');
        });
    }
};
