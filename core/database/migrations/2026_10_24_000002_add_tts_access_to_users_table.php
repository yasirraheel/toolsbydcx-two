<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'tts_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('tts_access')->default(false)->after('google_flow_account_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'tts_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['tts_access']);
                $table->dropColumn('tts_access');
            });
        }
    }
};
