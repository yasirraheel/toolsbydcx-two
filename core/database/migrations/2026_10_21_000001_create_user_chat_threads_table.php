<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32)->default('chatgpt');
            $table->string('chat_id', 128);
            $table->string('title', 300)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'chat_id']);
            $table->index(['user_id', 'provider', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_chat_threads');
    }
};
