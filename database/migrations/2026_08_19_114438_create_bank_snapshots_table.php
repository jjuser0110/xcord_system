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
        Schema::create('bank_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_setting_id')->constrained('bank_settings')->onDelete('cascade');
            $table->decimal('capital', 15, 2);
            $table->date('snapshot_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_snapshots');
    }
};
