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
        Schema::create('corporate_tax_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tax_jurisdiction_id')->constrained()->cascadeOnDelete();
            $t->integer('tax_year');
            $t->enum('status', ['draft', 'published'])->default('published');
            // rate can be percentage (e.g. 30.0000) or amount (flat minimum tax)
            $t->enum('rate_type', ['percentage', 'amount'])->default('percentage');
            $t->decimal('rate_value', 9, 4)->default(0);   // percent or amount depending on type
            $t->decimal('minimum_tax_amount', 18, 2)->nullable(); // optional min tax
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->timestamps();

            $t->unique(['tax_jurisdiction_id', 'tax_year'], 'u_cit_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_tax_versions');
    }
};
