<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mapping_profiles', function (Blueprint $table) {
            $table->string('entity', 32)->nullable()->after('name');
            $table->index('entity');
        });
    }

    public function down(): void
    {
        Schema::table('mapping_profiles', function (Blueprint $table) {
            $table->dropIndex(['entity']);
            $table->dropColumn('entity');
        });
    }
};

