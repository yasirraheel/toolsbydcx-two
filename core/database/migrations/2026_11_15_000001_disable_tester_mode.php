<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_tester')) {
            DB::table('users')->update(['is_tester' => 0]);
        }
    }

    public function down(): void
    {
        // Tester mode is intentionally retired and is not restored on rollback.
    }
};
