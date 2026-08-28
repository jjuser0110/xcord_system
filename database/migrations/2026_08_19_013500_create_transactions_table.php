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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no')->unique();
            $table->date('transaction_date');
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('bank_setting_id')->constrained('bank_settings')->restrictOnDelete();

            $table->string('type')->default('adjustment'); // own, customer, adjustment, etc.
            $table->string('transfer_direction'); // '+' or '-'
            $table->decimal('amount', 15, 2);

            $table->decimal('start_balance', 15, 2)->default(0);
            $table->decimal('end_balance', 15, 2)->default(0);

            $table->foreignId('purpose_id')->constrained('purposes')->restrictOnDelete();
            $table->text('remark_1')->nullable(); // Used for remarks/notes

            $table->string('column_color')->default('white');
            $table->string('closing_month')->index()->nullable();

            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
