<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_chat_threads', function (Blueprint $table) {
            $table->longText('messages_encrypted')->nullable()->after('title');
            $table->unsignedInteger('message_count')->default(0)->after('messages_encrypted');
            $table->string('share_token', 64)->nullable()->unique()->after('message_count');
            $table->boolean('is_public')->default(false)->after('share_token');
            $table->timestamp('archived_at')->nullable()->after('is_public');
            $table->index(['user_id', 'provider', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::table('user_chat_threads', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'provider', 'is_public']);
            $table->dropUnique(['share_token']);
            $table->dropColumn([
                'messages_encrypted',
                'message_count',
                'share_token',
                'is_public',
                'archived_at',
            ]);
        });
    }
};
