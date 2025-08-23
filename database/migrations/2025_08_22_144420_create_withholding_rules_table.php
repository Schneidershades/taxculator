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
        Schema::create('withholding_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_jurisdiction_id')->constrained()->cascadeOnDelete();
            $table->string('payee_type', 20)->default('individual'); // individual|company (extend as needed)
            $table->string('income_type', 100);                       // e.g., 'contract', 'rent'
            $table->decimal('rate', 9, 4);                            // percentage
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['tax_jurisdiction_id', 'income_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_rules');
    }
};
