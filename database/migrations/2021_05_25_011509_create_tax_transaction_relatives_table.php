<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxTransactionRelativesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_transaction_relatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_transaction_id')->nullable()->constrained();
            $table->integer('tax_relationable_id')->nullable();
            $table->string('tax_relationable_type')->nullable();
            $table->string('description')->nullable();
            $table->string('value')->nullable();
            $table->string('applied_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_transaction_relatives');
    }
}
