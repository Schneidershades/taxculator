<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('bank_transactions', 'category_account_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->foreignId('category_account_id')->nullable()->after('counterparty')->constrained('accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('bank_transactions', 'tax_tag')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->string('tax_tag')->nullable()->after('category_account_id');
            });
        }

        if (!Schema::hasColumn('bank_transactions', 'categorized_at')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->timestamp('categorized_at')->nullable()->after('tax_tag');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_transactions', 'category_account_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_account_id');
            });
        }
        $drops = [];
        if (Schema::hasColumn('bank_transactions', 'tax_tag')) $drops[] = 'tax_tag';
        if (Schema::hasColumn('bank_transactions', 'categorized_at')) $drops[] = 'categorized_at';
        if (!empty($drops)) {
            Schema::table('bank_transactions', function (Blueprint $table) use ($drops) {
                $table->dropColumn($drops);
            });
        }
    }
};
