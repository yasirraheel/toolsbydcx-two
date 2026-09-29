<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('extension_pairings', function (Blueprint $table) {
            $table->string('code_challenge', 128)->nullable()->after('pairing_code');
        });
    }
    public function down(): void {
        Schema::table('extension_pairings', function (Blueprint $table) {
            $table->dropColumn('code_challenge');
        });
    }
};
