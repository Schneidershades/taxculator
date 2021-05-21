<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryTaxDeductionAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_tax_deduction_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->foreignId('tax_deduction_attribute_id')->nullable()->constrained();
            $table->boolean('deduction_type')->default(false);
            $table->boolean('value')->default(false);
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
        Schema::dropIfExists('country_tax_deduction_attributes');
    }
}
