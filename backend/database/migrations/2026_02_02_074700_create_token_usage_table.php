<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_usage', function (Blueprint $table) {
            $table->id();
            $table->string('ad_id'); // Use string for UUID
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('token_type', ['prompt', 'output', 'total']);
            $table->unsignedInteger('tokens_used');
            $table->decimal('cost_dkk', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'token_type']);
            
            // Add foreign key for ads table manually
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_usage');
    }
};
