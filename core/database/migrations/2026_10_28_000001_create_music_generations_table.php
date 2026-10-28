<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 20)->default('simple');
            $table->string('title', 80)->nullable();
            $table->string('vocal_gender', 1)->nullable();
            $table->boolean('make_instrumental')->default(false);
            $table->boolean('async')->default(true);
            $table->text('description')->nullable();
            $table->text('lyrics')->nullable();
            $table->text('tags')->nullable();
            $table->string('task_id', 180)->nullable()->index();
            $table->string('status', 30)->default('queued')->index();
            $table->string('audio_path', 255)->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->json('provider_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_generations');
    }
};
