<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('plans', 'frontend_visible')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->boolean('frontend_visible')
                    ->default(true)
                    ->after('status')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'frontend_visible')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropIndex(['frontend_visible']);
                $table->dropColumn('frontend_visible');
            });
        }
    }
};
