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
        Schema::create('corporate_tax_losses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id');      // your company/client id
            $t->integer('tax_year');                   // year the loss originated
            $t->decimal('original_amount', 18, 2);
            $t->decimal('remaining_amount', 18, 2);
            $t->date('expires_at')->nullable();        // if your regime has expiry
            $t->timestamps();

            $t->index(['company_id', 'tax_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_tax_losses');
    }
};
