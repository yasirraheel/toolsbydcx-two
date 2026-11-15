<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_social_media')) {
            Schema::create('plan_social_media', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_id');
                $table->unsignedBigInteger('social_media_id');
                $table->timestamps();

                $table->unique(['plan_id', 'social_media_id']);
                $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
                $table->foreign('social_media_id')->references('id')->on('social_media')->cascadeOnDelete();
            });
        }

        // Convert the previous account-level plan selections to platform-level
        // entitlements. Duplicates are ignored by the unique pivot key.
        if (Schema::hasTable('plan_account_listing') && Schema::hasColumn('account_listings', 'social_media_id')) {
            $legacyRows = DB::table('plan_account_listing as pa')
                ->join('account_listings as al', 'al.id', '=', 'pa.account_listing_id')
                ->whereNotNull('al.social_media_id')
                ->select('pa.plan_id', 'al.social_media_id')
                ->distinct()
                ->get();

            foreach ($legacyRows as $row) {
                DB::table('plan_social_media')->insertOrIgnore([
                    'plan_id' => (int) $row->plan_id,
                    'social_media_id' => (int) $row->social_media_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_social_media');
    }
};
