<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->timestamp('subscribed_at')->nullable()->after('last_login_at');
            $table->timestamp('expires_at')->nullable()->after('subscribed_at');
            $table->smallInteger('subscription_months')->unsigned()->default(12)->after('expires_at');
            $table->string('subscription_plan', 32)->default('standard')->after('subscription_months');
            $table->text('subscription_notes')->nullable()->after('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'subscribed_at',
                'expires_at',
                'subscription_months',
                'subscription_plan',
                'subscription_notes',
            ]);
        });
    }
};