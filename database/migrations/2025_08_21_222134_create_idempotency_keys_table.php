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
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('method', 10);
            $table->string('endpoint', 191); // request path
            $table->string('request_hash', 64)->index(); // sha256 of normalized body
            $table->unsignedBigInteger('tax_transaction_id')->nullable()->index();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->longText('response_body')->nullable(); // JSON response
            $table->timestamps();

            $table->unique(['key', 'user_id', 'method', 'endpoint'], 'u_idem_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
