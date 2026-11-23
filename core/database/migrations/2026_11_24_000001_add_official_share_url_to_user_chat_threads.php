<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_chat_threads', function (Blueprint $table) {
            $table->string('official_share_url', 500)->nullable()->after('share_token');
            $table->index('official_share_url');
        });
    }

    public function down(): void
    {
        Schema::table('user_chat_threads', function (Blueprint $table) {
            $table->dropIndex(['official_share_url']);
            $table->dropColumn('official_share_url');
        });
    }
};
