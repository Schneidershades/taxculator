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
        Schema::create('tax_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_version_id')->constrained();
            $table->decimal('bracket_min', 18, 2)->default(0);
            $table->decimal('bracket_max', 18, 2)->nullable(); // null = no upper cap
            $table->enum('rate_type', ['percentage', 'amount']);
            $table->decimal('rate_value', 18, 4)->default(0);   // e.g., 7.0000 (%), or flat
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();
            $table->unique(['tax_version_id', 'ordering'], 'u_tariffs_version_order');
            $table->index('tax_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_tarrifs');
    }
};
