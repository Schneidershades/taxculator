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


        Schema::create('vat_invoices', function (Blueprint $t) {
            $t->id();
            $t->enum('direction', ['sale', 'purchase']);
            $t->unsignedBigInteger('company_id')->nullable();     // your company/customer id
            $t->date('issue_date');
            $t->string('period', 7);                              // YYYY-MM
            $t->string('country_code', 2);
            $t->string('state_code', 10)->nullable();
            $t->string('local_code', 50)->nullable();
            $t->integer('tax_year');

            $t->string('currency_code', 3)->nullable();

            $t->decimal('net_total', 18, 2)->default(0);
            $t->decimal('vat_total', 18, 2)->default(0);
            $t->decimal('gross_total', 18, 2)->default(0);

            $t->string('idempotency_key', 80)->nullable()->unique();
            $t->json('statement')->nullable(); // snapshot of lines & rates applied
            $t->timestamps();

            $t->index(['direction', 'period', 'country_code', 'state_code', 'local_code']);
            $t->index(['company_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_invoices');
    }
};
