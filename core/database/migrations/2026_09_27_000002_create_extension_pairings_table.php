<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('extension_pairings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('google_flow_account_id')->nullable();
            $table->string('pairing_code', 12)->nullable()->unique();
            $table->string('access_token', 128)->nullable()->unique();
            $table->string('installation_id', 64)->nullable();
            $table->string('uninstall_token', 64)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('browser')->nullable();
            $table->string('extension_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('google_flow_account_id')->references('id')->on('google_flow_accounts')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('extension_pairings'); }
};
