<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA journal_mode = WAL');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['transacted_at', 'status', 'amount'],
                'transactions_dashboard_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_dashboard_index');
        });
    }
};
