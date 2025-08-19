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
        Schema::create('tax_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_jurisdiction_id')->constrained();
            $table->year('tax_year')->index();                 // e.g., 2025
            $table->date('effective_from');                    // 2025-01-01
            $table->date('effective_to')->nullable();          // null = open-ended
            $table->unique(['tax_jurisdiction_id', 'tax_year']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_versions');
    }
};
