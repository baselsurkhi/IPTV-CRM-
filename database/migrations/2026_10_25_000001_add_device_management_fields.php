<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('installation_secret_hash', 64)->nullable();
            $table->string('login_method', 20)->default('activation')->index();
            $table->string('platform', 40)->nullable();
            $table->string('app_version', 80)->nullable();
            $table->json('device_metadata')->nullable();
            $table->string('public_ip', 45)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('devices', fn (Blueprint $table) => $table->dropColumn(['installation_secret_hash', 'login_method', 'platform', 'app_version', 'device_metadata', 'public_ip', 'last_seen_at']));
    }
};
