<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('flow_guard_models')) {
            Schema::create('flow_guard_models', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191)->unique();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('flow_guard_access', 'allowed_models')) {
            Schema::table('flow_guard_access', function (Blueprint $table) {
                $table->text('allowed_models')->nullable()->after('allowed_model');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('flow_guard_access', 'allowed_models')) {
            Schema::table('flow_guard_access', function (Blueprint $table) {
                $table->dropColumn('allowed_models');
            });
        }
        Schema::dropIfExists('flow_guard_models');
    }
};
