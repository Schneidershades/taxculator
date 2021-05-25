<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryTaxReliefClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_tax_relief_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->foreignId('tax_relief_class_id')->nullable()->constrained();
            $table->string('relief_type');
            $table->integer('value');
            $table->double('minimum_amount',13,2)->default(0);
            $table->double('maximum_amount',13,2)->default(0);
            $table->string('minimum_status')->nullable();
            $table->string('maximum_status')->nullable();
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
        Schema::dropIfExists('country_tax_relief_classes');
    }
}
