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
        Schema::create('bank_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_setting_id')->constrained('bank_settings')->cascadeOnDelete();
            $table->string('closing_month', 7); // Format: 'YYYY-MM' (e.g., '2026-07')
            $table->decimal('start_balance', 15, 2)->default(0);
            $table->decimal('end_balance', 15, 2)->default(0);
            $table->decimal('total_in', 15, 2)->default(0);
            $table->decimal('total_out', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->timestamps();

            $table->unique(['bank_setting_id', 'closing_month']);
            $table->index('closing_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_monthly_summaries');
    }
};
