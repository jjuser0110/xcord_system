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
        Schema::table('bank_phone_numbers', function (Blueprint $table) {
            $table->string('telco')->nullable()->after('bank_setting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_phone_numbers', function (Blueprint $table) {
            $table->dropColumn('telco');
        });
    }
};
