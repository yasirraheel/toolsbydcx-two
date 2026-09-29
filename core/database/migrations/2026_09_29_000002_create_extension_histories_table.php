<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('extension_histories')) {
            Schema::create('extension_histories', function (Blueprint $table) {
                $table->id();
                $table->string('version', 50);
                $table->string('filename', 255);
                $table->string('file_path', 255);
                $table->string('file_size', 50)->nullable();
                $table->string('type', 50)->default('flow');
                $table->boolean('is_current')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_histories');
    }
};
