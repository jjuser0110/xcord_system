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
            $table->boolean('show_on_received_from_provider')->default(false);
            $table->boolean('show_on_topup_to_provider')->default(false);
            $table->boolean('show_on_transfer_for_merchant')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purposes', function (Blueprint $table) {
            $table->dropColumn([
                'show_on_received_from_provider',
                'show_on_topup_to_provider',
                'show_on_transfer_for_merchant',
            ]);
        });
    }
};
