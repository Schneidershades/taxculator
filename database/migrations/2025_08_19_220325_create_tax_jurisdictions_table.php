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
        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('tax_jurisdictions')->nullOnDelete();
            $table->enum('level', ['country', 'state', 'local'])->index();
            $table->string('country_code', 2)->nullable()->index();  // e.g., NG
            $table->string('state_code', 10)->nullable()->index();   // e.g., LA (Lagos)
            $table->string('local_code', 50)->nullable()->index();   // e.g., IKEJA
            $table->string('name');
            $table->string('currency_code', 3)->nullable();          // NGN, GHS, ...
            $table->timestamps();
            $table->index(['level', 'country_code', 'state_code', 'local_code'], 'i_jurs_lookup');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_jurisdictions');
    }
};
