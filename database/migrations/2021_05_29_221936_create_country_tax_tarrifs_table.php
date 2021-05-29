<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryTaxTarrifsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_tax_tarrifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->string('type')->nullable();
            $table->double('fixed_amount',13,2)->default(0);
            $table->double('fixed_percentage',13,2)->default(0);
            $table->double('min_range_amount',13,2)->default(0);
            $table->double('max_range_amount',13,2)->default(0);
            $table->double('min_range_percentage',13,2)->default(0);
            $table->double('max_range_percentage',13,2)->default(0);
            $table->boolean('above_fixed_amount_range')->default(false);
            $table->boolean('below_fixed_amount_range')->default(false);
            $table->boolean('above_fixed_percentage_range')->default(false);
            $table->boolean('below_fixed_percentage_range')->default(false);
            $table->integer('ordering_id');
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
        Schema::dropIfExists('country_tax_tarrifs');
    }
}
