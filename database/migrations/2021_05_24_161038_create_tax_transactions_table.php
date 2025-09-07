<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->json('input_snapshot')->nullable();
            $table->json('versions_snapshot')->nullable();
            $table->string('rules_hash', 64)->nullable(); // sha256
            $table->json('statement')->nullable();
            $table->string('display_currency', 3)->nullable();
            $table->json('fx_snapshot')->nullable()->after('display_currency');
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
        Schema::dropIfExists('tax_transactions');
    }
};
