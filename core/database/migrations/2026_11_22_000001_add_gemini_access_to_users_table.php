<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'gemini_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('gemini_access')->default(false)->after('tts_access')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'gemini_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['gemini_access']);
                $table->dropColumn('gemini_access');
            });
        }
    }
};
