<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('flow_guard_access', 'full_control')) {
            Schema::table('flow_guard_access', function (Blueprint $table) {
                $table->boolean('full_control')->default(false)->after('enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('flow_guard_access', 'full_control')) {
            Schema::table('flow_guard_access', function (Blueprint $table) {
                $table->dropColumn('full_control');
            });
        }
    }
};
