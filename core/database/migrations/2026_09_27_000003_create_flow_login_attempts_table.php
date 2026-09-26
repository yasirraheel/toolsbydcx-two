<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('flow_login_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('google_flow_account_id');
            $table->string('status')->default('in_progress'); // in_progress, success, cancelled, failed
            $table->timestamp('expires_at');
            $table->boolean('backup_code_used')->default(false);
            $table->integer('otp_attempt_count')->default(0);
            $table->string('outcome')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('flow_login_attempts'); }
};
