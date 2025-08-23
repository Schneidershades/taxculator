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
        Schema::create('withholding_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id'); // link to your user/customer ID
            $table->foreignId('withholding_rule_id')->constrained()->cascadeOnDelete();
            $table->integer('tax_year');                  // which PIT year this credit belongs to
            $table->string('period', 20)->nullable();     // e.g. '2025-05'
            $table->decimal('base_amount', 18, 2);
            $table->decimal('wht_amount', 18, 2);         // original credit amount
            $table->decimal('remaining_amount', 18, 2);   // decremented as applied
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['beneficiary_id', 'tax_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_credits');
    }
};
