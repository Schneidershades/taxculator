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
        Schema::create('tax_class_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_version_id')->constrained();
            $table->foreignId('tax_class_id')->constrained();
            $table->boolean('require_deduction')->default(false);
            $table->unique(['tax_version_id', 'tax_class_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_class_links');
    }
};
