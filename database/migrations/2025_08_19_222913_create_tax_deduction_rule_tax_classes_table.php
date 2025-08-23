<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_deduction_rule_tax_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_deduction_rule_id')->constrained();
            $table->foreignId('tax_class_id')->constrained();
            $table->timestamps();
            $table->unique(['tax_deduction_rule_id', 'tax_class_id'], 'u_rule_baseclass');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_deduction_rule_tax_classes');
    }
};
