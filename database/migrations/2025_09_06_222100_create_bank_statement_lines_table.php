<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_statement_lines')) return;

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained('bank_statements')->cascadeOnDelete();
            $table->timestamp('posted_at');
            $table->decimal('amount', 18, 2);
            $table->string('description')->nullable();
            $table->string('counterparty')->nullable();
            $table->string('external_id')->nullable();
            $table->string('hash')->index();
            $table->foreignId('matched_bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
            $table->unique(['bank_statement_id','hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};

