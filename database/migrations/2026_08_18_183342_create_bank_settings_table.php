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
        Schema::create('bank_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->restrictOnDelete();
            $table->string('account_no')->nullable();
            $table->string('owner_name')->nullable();
            $table->decimal('capital', 15, 2)->default(0);
            $table->string('phone_number')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('color')->default('white');
            $table->string('type')->nullable();
            $table->string('path')->nullable();
            $table->integer('for_cdm')->nullable();
            $table->integer('for_withdraw')->nullable();
            $table->integer('is_active')->default(1);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_settings');
    }
};
