<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryApplyClassToDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_class_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_tax_deduction_class_id')->nullable()->constrained('country_tax_deduction_classes');
            $table->foreignId('country_tax_class_id')->nullable()->constrained('country_tax_classes');
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
        Schema::dropIfExists('country_class_deductions');
    }
}
