<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_statements')) return;

        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('ending_balance', 18, 2)->nullable();
            $table->string('status', 24)->default('imported'); // imported|reconciled
            $table->string('path')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','bank_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};

