<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'plain_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('plain_password', 255)->nullable()->after('password');
            });
        }

        // Populate plain_password for existing users so admin/resellers can copy actual passwords
        $users = DB::table('users')->whereNull('plain_password')->orWhere('plain_password', '')->get();
        foreach ($users as $user) {
            $readablePassword = 'User' . rand(100000, 999999);
            DB::table('users')->where('id', $user->id)->update([
                'plain_password' => $readablePassword,
                'password'       => Hash::make($readablePassword),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'plain_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('plain_password');
            });
        }
    }
};
