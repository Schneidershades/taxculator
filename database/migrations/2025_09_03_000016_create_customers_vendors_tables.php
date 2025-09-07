<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('external_id')->nullable();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('tax_id')->nullable();
            $t->json('address')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['tenant_id', 'external_id']);
            $t->unique(['tenant_id', 'name', 'email']);
        });

        Schema::create('vendors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('external_id')->nullable();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('tax_id')->nullable();
            $t->json('address')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['tenant_id', 'external_id']);
            $t->unique(['tenant_id', 'name', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
    }
};

