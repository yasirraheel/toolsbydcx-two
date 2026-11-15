<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('plans', 'google_flow_account_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unsignedBigInteger('google_flow_account_id')
                    ->nullable()
                    ->after('flow_access')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'google_flow_account_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('google_flow_account_id');
            });
        }
    }
};
