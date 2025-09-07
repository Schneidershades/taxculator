<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bank_transactions', 'cleared_at')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->timestamp('cleared_at')->nullable()->after('categorized_at');
            });
        }
        if (!Schema::hasColumn('bank_transactions', 'cleared_statement_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->foreignId('cleared_statement_id')->nullable()->after('cleared_at')->constrained('bank_statements')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_transactions', 'cleared_statement_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cleared_statement_id');
            });
        }
        if (Schema::hasColumn('bank_transactions', 'cleared_at')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropColumn('cleared_at');
            });
        }
    }
};

