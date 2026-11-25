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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            // deposit = setor, withdraw = tarik, exchange_out / exchange_in = tukar mata uang
            $table->string('type', 20);
            $table->char('currency', 3);                 // IDR | AUD
            $table->decimal('amount', 18, 2);            // nominal dalam mata uang aslinya (selalu positif)
            $table->decimal('rate', 18, 4)->default(1);  // kurs ke IDR saat transaksi (IDR = 1)
            $table->decimal('amount_idr', 20, 2);        // amount x rate
            $table->date('date');
            $table->string('method', 20)->default('cash');
            $table->string('note', 255)->nullable();
            $table->uuid('exchange_group')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'currency']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('transactions');
    }
};
