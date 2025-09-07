<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->nullable(); // mono|okra|stitch|csv
            $table->string('masked_number')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('external_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('ledger_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};

