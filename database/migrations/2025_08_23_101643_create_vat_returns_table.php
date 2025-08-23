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
        Schema::create('vat_returns', function (Blueprint $t) {
            $t->id();
            $t->string('period', 7); // YYYY-MM
            $t->string('country_code', 2);
            $t->string('state_code', 10)->nullable();
            $t->string('local_code', 50)->nullable();
            $t->integer('tax_year');

            $t->decimal('output_vat', 18, 2)->default(0); // sales VAT
            $t->decimal('input_vat', 18, 2)->default(0);  // purchases VAT
            $t->decimal('net_vat', 18, 2)->default(0);    // output - input

            $t->enum('status', ['draft', 'filed'])->default('filed');

            $t->json('statement')->nullable(); // snapshot of totals, buckets, source invoice ids
            $t->timestamps();

            $t->unique(['period', 'country_code', 'state_code', 'local_code'], 'u_vat_return_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_returns');
    }
};
