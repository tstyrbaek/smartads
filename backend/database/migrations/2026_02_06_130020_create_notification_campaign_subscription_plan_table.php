<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaign_subscription_plan', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('subscription_plan_id');

            $table->primary(['campaign_id', 'subscription_plan_id']);

            $table->foreign('campaign_id', 'ncsp_campaign_fk')
                ->references('id')
                ->on('notification_campaigns')
                ->cascadeOnDelete();

            $table->foreign('subscription_plan_id', 'ncsp_plan_fk')
                ->references('id')
                ->on('subscription_plans')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_campaign_subscription_plan');
    }
};
