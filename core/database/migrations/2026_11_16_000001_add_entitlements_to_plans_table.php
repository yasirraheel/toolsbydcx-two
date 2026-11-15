<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('flow_access')->default(false)->after('features');
            $table->boolean('tts_access')->default(false)->after('flow_access');
            $table->unsignedBigInteger('tts_credit_limit')->nullable()->after('tts_access');
        });

        // Before this setting existed, an assigned Flow account was enough
        // for an active user. Preserve that behavior for existing plans;
        // newly created plans still default to Flow disabled until selected.
        DB::table('plans')->update(['flow_access' => true]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['flow_access', 'tts_access', 'tts_credit_limit']);
        });
    }
};
