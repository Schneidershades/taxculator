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
        Schema::create('corporate_tax_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('idempotency_key', 80)->nullable()->unique();
            $t->json('input_snapshot')->nullable();
            $t->json('version_snapshot')->nullable();
            $t->string('rules_hash', 64)->nullable();
            $t->json('statement')->nullable();
            $t->timestamps();

            $t->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_tax_transactions');
    }
};
