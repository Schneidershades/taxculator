<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('external_id')->nullable();
            $t->string('number')->index();
            $t->date('date');
            $t->date('due_date')->nullable();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('currency', 3)->nullable();
            $t->decimal('total', 18, 2)->default(0);
            $t->string('status', 24)->default('imported'); // imported|posted|paid
            $t->foreignId('journal_transaction_id')->nullable()->constrained('journal_transactions')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','number']);
        });

        Schema::create('invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $t->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $t->string('description')->nullable();
            $t->decimal('qty', 18, 4)->default(1);
            $t->decimal('unit_price', 18, 4)->default(0);
            $t->decimal('amount', 18, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('bills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('external_id')->nullable();
            $t->string('number')->index();
            $t->date('date');
            $t->date('due_date')->nullable();
            $t->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $t->string('currency', 3)->nullable();
            $t->decimal('total', 18, 2)->default(0);
            $t->string('status', 24)->default('imported'); // imported|posted|paid
            $t->foreignId('journal_transaction_id')->nullable()->constrained('journal_transactions')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','number']);
        });

        Schema::create('bill_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $t->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $t->string('description')->nullable();
            $t->decimal('qty', 18, 4)->default(1);
            $t->decimal('unit_price', 18, 4)->default(0);
            $t->decimal('amount', 18, 2)->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_lines');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};

