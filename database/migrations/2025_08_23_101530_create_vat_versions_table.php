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
        Schema::create('vat_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tax_jurisdiction_id')->constrained()->cascadeOnDelete();
            $t->integer('tax_year');
            $t->enum('status', ['draft', 'published'])->default('published');
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->timestamps();

            $t->unique(['tax_jurisdiction_id', 'tax_year'], 'u_vat_version');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_versions');
    }
};
