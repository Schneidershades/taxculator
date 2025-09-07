<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('matcher_type', 16); // contains|equals|regex|amount_range
            $table->string('field', 32)->default('description'); // description|counterparty
            $table->string('value')->nullable(); // used for contains/equals/regex
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->foreignId('target_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('tax_tag')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('priority')->default(100); // lower runs first
            $table->timestamps();
            $table->index(['tenant_id', 'active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};

