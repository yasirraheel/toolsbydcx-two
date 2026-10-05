<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'google_flow_account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('google_flow_account_id')->nullable()->after('plan_id')->index();
            });
        }

        // Migrate existing assignments from google_flow_accounts.assigned_to_user_id
        if (Schema::hasTable('google_flow_accounts')) {
            $accounts = DB::table('google_flow_accounts')->whereNotNull('assigned_to_user_id')->get();
            foreach ($accounts as $acc) {
                if (is_numeric($acc->assigned_to_user_id)) {
                    DB::table('users')->where('id', (int) $acc->assigned_to_user_id)
                        ->update(['google_flow_account_id' => $acc->id]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'google_flow_account_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('google_flow_account_id');
            });
        }
    }
};
