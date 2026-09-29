<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('extension_pairings', function (Blueprint $table) {
            $table->string('pairing_code', 128)->nullable()->change();
            $table->string('code_challenge', 43)->nullable();
        });
        Schema::table('flow_login_attempts', function (Blueprint $table) {
            $table->foreignId('extension_pairing_id')->nullable()->constrained('extension_pairings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('extension_pairings', function (Blueprint $table) { $table->dropColumn('code_challenge'); });
        Schema::table('flow_login_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('extension_pairing_id');
        });
    }
};
