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


        Schema::create('vat_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vat_invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description')->nullable();
            $t->string('category_code', 30)->default('standard'); // matches vat_rates.code
            $t->decimal('net_amount', 18, 2);
            $t->decimal('vat_rate', 9, 4);   // stored for snapshot
            $t->decimal('vat_amount', 18, 2);
            $t->boolean('reverse_charge')->default(false);
            $t->string('place_of_supply_code', 10)->nullable();
            $t->string('exempt_reason')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_invoice_lines');
    }
};
