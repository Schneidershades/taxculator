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
        Schema::create('country_apply_class_to_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_deduction_class_id')->nullable()->constrained();
            $table->foreignId('tax_class_id')->nullable()->constrained();
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
        Schema::dropIfExists('country_apply_class_to_deductions');
    }
}
