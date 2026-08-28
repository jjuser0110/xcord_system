<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->restrictOnDelete();
            $table->string('owner_name');
            $table->decimal('capital', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0); // Tracks current balance
            $table->string('color')->default('white');
            $table->string('path')->nullable();
            $table->integer('is_active')->default(1);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_settings');
    }
};
