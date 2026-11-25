<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 20)->default('pink')->after('emoji');
            $table->boolean('is_admin')->default(false)->after('theme');
            $table->timestamp('approved_at')->nullable()->after('is_admin');
        });

        // Akun yang sudah ada: otomatis disetujui, akun pertama jadi admin.
        DB::table('users')->update(['approved_at' => now()]);
        $first = DB::table('users')->orderBy('id')->value('id');
        if ($first) {
            DB::table('users')->where('id', $first)->update(['is_admin' => true]);
        }

        // Kurs mata uang kecil (mis. VND, IRR) butuh lebih banyak angka di belakang koma.
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('rate', 20, 8)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('rate', 18, 4)->default(1)->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'is_admin', 'approved_at']);
        });
    }
};
