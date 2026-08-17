<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->string('user_name', 100)->index();
            $table->decimal('amount', 14, 2);
            $table->string('type', 10)->index();
            $table->string('status', 20)->index();
            $table->string('description')->nullable();
            $table->timestamp('transacted_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
