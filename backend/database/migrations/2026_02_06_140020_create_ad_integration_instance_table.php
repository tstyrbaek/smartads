<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_integration_instance', function (Blueprint $table) {
            $table->ulid('ad_id');
            $table->unsignedBigInteger('integration_instance_id');

            $table->string('status')->default('selected');
            $table->dateTime('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->primary(['ad_id', 'integration_instance_id']);

            $table->foreign('ad_id', 'aii_ad_fk')->references('id')->on('ads')->cascadeOnDelete();
            $table->foreign('integration_instance_id', 'aii_instance_fk')->references('id')->on('integration_instances')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_integration_instance');
    }
};
