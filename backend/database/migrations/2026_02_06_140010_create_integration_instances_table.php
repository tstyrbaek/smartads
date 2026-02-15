<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('integration_key');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->json('credentials')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'integration_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_instances');
    }
};
