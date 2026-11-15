<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_account_listing')) {
            Schema::create('plan_account_listing', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_id');
                $table->unsignedBigInteger('account_listing_id');
                $table->timestamps();

                $table->unique(['plan_id', 'account_listing_id']);
                $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
                $table->foreign('account_listing_id')->references('id')->on('account_listings')->cascadeOnDelete();
            });
        }

        // Preserve the existing one-plan-per-account assignments while moving
        // plan access to the new many-to-many source of truth.
        if (Schema::hasColumn('account_listings', 'plan_id')) {
            $legacyRows = DB::table('account_listings')
                ->where('plan_id', '>', 0)
                ->get(['plan_id', 'id']);

            foreach ($legacyRows as $row) {
                DB::table('plan_account_listing')->updateOrInsert(
                    [
                        'plan_id' => (int) $row->plan_id,
                        'account_listing_id' => (int) $row->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_account_listing');
    }
};
