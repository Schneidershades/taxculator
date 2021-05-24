<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryTaxDeductionClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_tax_deduction_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->foreignId('tax_deduction_class_id')->nullable()->constrained();
            $table->string('deduction_type');
            $table->double('value',13,2)->default(0);
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
        Schema::dropIfExists('country_tax_deduction_classes');
    }
}
