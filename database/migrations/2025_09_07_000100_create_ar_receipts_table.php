<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->date('date');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $t->string('currency_code', 3)->nullable();
            $t->decimal('amount', 18, 2);
            $t->foreignId('journal_transaction_id')->nullable()->constrained('journal_transactions')->nullOnDelete();
            $t->timestamps();
            $t->index(['tenant_id','date']);
        });

        Schema::create('ar_receipt_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ar_receipt_id')->constrained('ar_receipts')->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $t->decimal('amount', 18, 2);
            $t->timestamps();
            $t->unique(['ar_receipt_id','invoice_id'], 'u_ar_alloc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_receipt_allocations');
        Schema::dropIfExists('ar_receipts');
    }
};

