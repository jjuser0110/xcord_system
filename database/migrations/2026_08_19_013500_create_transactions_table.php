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

            // Primary Bank account (or source bank account for 'own' transfers)
            $table->foreignId('bank_setting_id')->constrained('bank_settings')->restrictOnDelete();

            // Type: 'own' or 'customer'
            $table->string('type');

            // Target bank account if type = 'own' (Bank In)
            $table->foreignId('target_bank_setting_id')->nullable()->constrained('bank_settings')->nullOnDelete();

            $table->string('transfer_direction'); // '+' or '-'
            $table->decimal('amount', 15, 2);

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();

            // Running balances to match monthly statements easily
            $table->decimal('start_balance', 15, 2)->default(0);
            $table->decimal('end_balance', 15, 2)->default(0);
            $table->decimal('target_start_balance', 15, 2)->default(0);
            $table->decimal('target_end_balance', 15, 2)->default(0);

            $table->foreignId('purpose_id')->constrained('purposes')->restrictOnDelete();

            $table->text('remark_1')->nullable();
            $table->text('remark_2')->nullable();

            // Customizable row/column color
            $table->string('column_color')->default('white');

            // Grouping for monthly closing (format: 'YYYY-MM')
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
