<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('warzone_purchased_links');
        DB::table('migrations')->where('migration', 'like', '%2026_09_03_000001_create_warzone_purchased_links_table%')->delete();
        \Illuminate\Support\Facades\Cache::forget('warzone_gemini_autobuy_state');
        if (file_exists(storage_path('app/warzone_autobuy.json'))) {
            @unlink(storage_path('app/warzone_autobuy.json'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
