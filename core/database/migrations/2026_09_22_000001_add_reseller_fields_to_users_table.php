<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_reseller')) {
                $table->tinyInteger('is_reseller')->default(0)->after('is_exclusive')->index();
            }
            if (!Schema::hasColumn('users', 'reseller_id')) {
                $table->unsignedBigInteger('reseller_id')->nullable()->after('is_reseller')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_reseller')) {
                $table->dropColumn('is_reseller');
            }
            if (Schema::hasColumn('users', 'reseller_id')) {
                $table->dropColumn('reseller_id');
            }
        });
    }
};
