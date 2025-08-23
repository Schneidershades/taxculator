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


        Schema::create('vat_rates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vat_version_id')->constrained()->cascadeOnDelete();
            $t->string('code', 30); // standard, reduced, zero, exempt, custom code
            $t->string('name')->nullable();
            $t->enum('rate_type', ['percentage', 'amount'])->default('percentage');
            $t->decimal('rate_value', 9, 4)->default(0); // 7.5000 => 7.5%
            $t->timestamps();

            $t->unique(['vat_version_id', 'code'], 'u_vat_rate_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_rates');
    }
};
