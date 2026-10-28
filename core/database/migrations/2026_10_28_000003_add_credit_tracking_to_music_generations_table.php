<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('music_generations', function (Blueprint $table) {
            if (!Schema::hasColumn('music_generations', 'credit_cost')) {
                $table->unsignedInteger('credit_cost')->default(0)->after('status');
            }
            if (!Schema::hasColumn('music_generations', 'credits_refunded')) {
                $table->boolean('credits_refunded')->default(false)->after('credit_cost');
            }
        });
    }

    public function down(): void
    {
        $drop = array_values(array_filter([
            Schema::hasColumn('music_generations', 'credits_refunded') ? 'credits_refunded' : null,
            Schema::hasColumn('music_generations', 'credit_cost') ? 'credit_cost' : null,
        ]));
        if ($drop) {
            Schema::table('music_generations', fn (Blueprint $table) => $table->dropColumn($drop));
        }
    }
};
