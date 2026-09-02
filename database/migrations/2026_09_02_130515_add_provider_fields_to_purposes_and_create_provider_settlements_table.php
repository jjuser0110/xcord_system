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
            $table->boolean('has_provider_settlement')->default(false)->after('is_global');
            $table->string('provider_name')->nullable()->after('has_provider_settlement');
        });

        Schema::create('provider_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('purpose_id')->nullable()->constrained('purposes')->nullOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('bank_name')->nullable();
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->string('provider_name');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_settlements');

        Schema::table('purposes', function (Blueprint $table) {
            $table->dropColumn(['has_provider_settlement', 'provider_name']);
        });
    }
};
