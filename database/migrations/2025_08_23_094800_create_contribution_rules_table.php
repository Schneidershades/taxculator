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
        Schema::create('contribution_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tax_version_id')->constrained()->cascadeOnDelete();

            $t->string('name');                               // e.g. "Pension"
            $t->enum('base_type', ['classes', 'gross'])->default('classes');
            $t->enum('rate_type', ['percentage', 'amount'])->default('percentage');

            // percentage: 8.0000 means 8%; amount: direct currency amount
            $t->decimal('employee_rate', 9, 4)->nullable();
            $t->decimal('employer_rate', 9, 4)->nullable();

            // caps/floors on computed contribution (per side)
            $t->decimal('employee_cap', 18, 2)->nullable();
            $t->decimal('employer_cap', 18, 2)->nullable();
            $t->decimal('employee_floor', 18, 2)->nullable();
            $t->decimal('employer_floor', 18, 2)->nullable();

            // when rules exist at multiple levels (country/state/local)
            $t->enum('combine_mode', ['stack', 'override'])->default('stack');

            $t->boolean('used')->default(true);
            $t->timestamps();

            $t->index(['tax_version_id']);
        });

        Schema::create('contribution_rule_base_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contribution_rule_id')->constrained('contribution_rules')->cascadeOnDelete();
            $t->foreignId('tax_class_id')->constrained('tax_classes')->cascadeOnDelete();
            $t->unique(['contribution_rule_id', 'tax_class_id'], 'u_rule_class');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_rules');
    }
};
