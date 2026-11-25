<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname', 40)->nullable()->after('name');
            $table->string('color', 9)->default('#ec4899')->after('nickname');
            $table->string('emoji', 16)->default('🌸')->after('color');
            $table->unsignedBigInteger('monthly_target')->default(0)->after('emoji'); // target nabung per bulan (IDR)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nickname', 'color', 'emoji', 'monthly_target']);
        });
    }
};
