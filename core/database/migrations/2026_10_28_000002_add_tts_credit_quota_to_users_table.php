<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [];

        if (!Schema::hasColumn('users', 'tts_credit_limit')) {
            $columns['tts_credit_limit'] = fn (Blueprint $table) => $table->unsignedBigInteger('tts_credit_limit')->nullable()->after('tts_access');
        }
        if (!Schema::hasColumn('users', 'tts_credits_used')) {
            $columns['tts_credits_used'] = fn (Blueprint $table) => $table->unsignedBigInteger('tts_credits_used')->default(0)->after('tts_credit_limit');
        }

        if ($columns) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                foreach ($columns as $add) {
                    $add($table);
                }
            });
        }
    }

    public function down(): void
    {
        $drop = array_values(array_filter([
            Schema::hasColumn('users', 'tts_credits_used') ? 'tts_credits_used' : null,
            Schema::hasColumn('users', 'tts_credit_limit') ? 'tts_credit_limit' : null,
        ]));

        if ($drop) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($drop));
        }
    }
};
