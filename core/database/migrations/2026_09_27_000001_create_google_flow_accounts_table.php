<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('google_flow_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable(); // friendly name for admin
            $table->string('email')->unique();
            $table->text('password_encrypted'); // Crypt::encryptString()
            $table->text('totp_secret_encrypted')->nullable(); // base32 TOTP secret
            $table->json('backup_codes')->nullable(); // array of unused 8-digit codes
            $table->string('status')->default('active'); // active, disabled, locked
            $table->string('assigned_to_user_id')->nullable(); // FK to users.id
            $table->integer('active_sessions')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('google_flow_accounts'); }
};
