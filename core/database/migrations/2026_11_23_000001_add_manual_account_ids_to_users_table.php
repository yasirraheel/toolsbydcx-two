<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'manual_account_ids')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('manual_account_ids')->nullable()->after('account_ids');
            });
        }

        // Existing no-plan assignments were already direct assignments. Plan
        // users previously had no supported manual override, so their current
        // IDs are treated as plan-managed and rebuilt on the next sync.
        DB::table('users')->select(['id', 'plan_id', 'account_ids'])->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                $ids = json_decode((string) ($user->account_ids ?? '[]'), true);
                $ids = is_array($ids)
                    ? array_values(array_unique(array_map('intval', $ids)))
                    : [];
                $manual = ((int) ($user->plan_id ?? 0) > 0) ? [] : $ids;
                DB::table('users')->where('id', $user->id)->update([
                    'manual_account_ids' => json_encode($manual),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'manual_account_ids')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('manual_account_ids');
            });
        }
    }
};
