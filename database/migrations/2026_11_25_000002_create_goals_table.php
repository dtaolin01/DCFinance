<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // pembuat target
            $table->boolean('is_shared')->default(false);                  // target bersama (berdua)
            $table->string('name', 100);
            $table->string('emoji', 16)->default('🎯');
            $table->string('color', 9)->default('#ec4899');
            $table->decimal('target_amount', 18, 2);
            $table->char('target_currency', 3)->default('IDR');
            $table->date('deadline')->nullable();
            $table->unsignedTinyInteger('priority')->default(2); // 1 tinggi, 2 sedang, 3 rendah
            $table->text('notes')->nullable();
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
