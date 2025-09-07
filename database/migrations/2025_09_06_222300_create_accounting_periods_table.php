<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_periods')) return;

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->string('status', 10)->default('open'); // open|closed
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};

