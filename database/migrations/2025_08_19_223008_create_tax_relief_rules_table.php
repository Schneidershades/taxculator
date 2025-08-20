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
        Schema::create('tax_relief_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_version_id')->constrained();
            $table->foreignId('tax_relief_class_id')->constrained();
            $table->enum('relief_type', ['amount', 'percentage']);
            $table->decimal('value', 18, 4)->default(0);
            $table->decimal('minimum_amount', 18, 2)->default(0);
            $table->decimal('maximum_amount', 18, 2)->nullable();
            $table->enum('minimum_status', ['static', 'unlimited'])->default('static');
            $table->enum('maximum_status', ['static', 'unlimited'])->default('unlimited');
            $table->enum('combine_mode', ['stack', 'override'])->default('stack');
            $table->unique(['tax_version_id', 'tax_relief_class_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_relief_rules');
    }
};
