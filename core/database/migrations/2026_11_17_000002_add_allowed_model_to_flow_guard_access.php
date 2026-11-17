<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('flow_guard_access', 'allowed_model')) {
            Schema::table('flow_guard_access', function (Blueprint $table) {
                $table->string('allowed_model', 191)->nullable()->after('generation_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('flow_guard_access', 'allowed_model')) {
            Schema::table('flow_guard_access', function (Blueprint $table) {
                $table->dropColumn('allowed_model');
            });
        }
    }
};
