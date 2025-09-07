<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->date('date');
            $t->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $t->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $t->string('currency_code', 3)->nullable();
            $t->decimal('amount', 18, 2);
            $t->foreignId('journal_transaction_id')->nullable()->constrained('journal_transactions')->nullOnDelete();
            $t->timestamps();
            $t->index(['tenant_id','date']);
        });

        Schema::create('ap_payment_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ap_payment_id')->constrained('ap_payments')->cascadeOnDelete();
            $t->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $t->decimal('amount', 18, 2);
            $t->timestamps();
            $t->unique(['ap_payment_id','bill_id'], 'u_ap_alloc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_payment_allocations');
        Schema::dropIfExists('ap_payments');
    }
};

