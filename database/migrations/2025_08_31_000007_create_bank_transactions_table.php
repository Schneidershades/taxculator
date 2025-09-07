<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('hash')->index();
            $table->timestamp('posted_at');
            $table->decimal('amount', 18, 2);
            $table->string('description')->nullable();
            $table->string('counterparty')->nullable();
            $table->foreignId('category_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('tax_tag')->nullable();
            $table->timestamp('categorized_at')->nullable();
            $table->json('raw')->nullable();
            $table->string('status', 24)->default('imported'); // imported|posted|ignored
            $table->foreignId('journal_transaction_id')->nullable()->constrained('journal_transactions')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'bank_account_id', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
