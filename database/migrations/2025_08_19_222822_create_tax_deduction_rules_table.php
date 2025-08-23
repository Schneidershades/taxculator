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
        Schema::create('tax_deduction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_version_id')->constrained();
            $table->foreignId('tax_deduction_class_id')->constrained();
            $table->enum('deduction_type', ['amount', 'percentage']);
            $table->decimal('value', 18, 4)->default(0);
            $table->enum('combine_mode', ['stack', 'override'])->default('stack');
            $table->timestamps();

            $table->unique(['tax_version_id', 'tax_deduction_class_id'], 'u_deductions_version_class');
            $table->index('tax_version_id');
            $table->index('tax_deduction_class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_deduction_rules');
    }
};
