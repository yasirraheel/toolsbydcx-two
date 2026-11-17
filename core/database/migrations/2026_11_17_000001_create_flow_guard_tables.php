<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_guard_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->unsignedBigInteger('credit_limit')->nullable();
            $table->unsignedBigInteger('credits_used')->default(0);
            $table->unsignedInteger('generation_cost')->default(100);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('flow_guard_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('installation_id', 128)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('flow_guard_credit_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('flow_guard_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status', 16)->default('reserved');
            $table->string('idempotency_key', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'idempotency_key']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('flow_guard_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('external_project_id', 191);
            $table->string('name')->nullable();
            $table->text('url');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'external_project_id']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_guard_projects');
        Schema::dropIfExists('flow_guard_credit_reservations');
        Schema::dropIfExists('flow_guard_sessions');
        Schema::dropIfExists('flow_guard_access');
    }
};
